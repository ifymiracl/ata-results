<?php

namespace Tests\Feature;

use App\Models\Result;
use App\Models\School;
use App\Models\Student;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResultsWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->school = School::where('slug', 'greenfield')->firstOrFail();
    }

    private function login(string $id)
    {
        return $this->post('/greenfield/login', ['identifier' => $id, 'pin' => '123456'])->assertRedirect('/greenfield/home');
    }

    private function logout(): void
    {
        $this->post('/greenfield/logout');
    }

    public function test_public_pages_render(): void
    {
        $this->get('/')->assertOk();
        $this->get('/find-school?q=green')->assertOk()->assertSee('Greenfield');
        $this->get('/register-school')->assertOk();
        $this->get('/greenfield')->assertOk()->assertSee('Greenfield Academy');
    }

    public function test_wrong_pin_is_rejected_and_throttled(): void
    {
        for ($i = 0; $i < 8; $i++) {
            $this->post('/greenfield/login', ['identifier' => 'TCH-0001', 'pin' => '000000'])->assertSessionHasErrors('identifier');
        }
        $this->post('/greenfield/login', ['identifier' => 'TCH-0001', 'pin' => '123456'])->assertSessionHasErrors('identifier');
    }

    public function test_full_publish_chain_and_family_visibility(): void
    {
        $session = $this->school->current_session; $term = $this->school->current_term;
        $students = Student::where('class_name', 'JSS 1')->get();

        // subject teachers enter and submit
        foreach (['TCH-0001' => 'Mathematics', 'TCH-0002' => 'English Language'] as $id => $subject) {
            $this->login($id);
            $scores = [];
            foreach ($students as $s) { $scores[$s->id] = ['ca1' => 10, 'ca2' => 12, 'exam' => 50]; }
            $q = ['class' => 'JSS 1', 'subject' => $subject, 'session' => $session, 'term' => $term];
            $this->post('/greenfield/scores/save', $q + ['score' => $scores])->assertSessionHas('ok');
            $this->post('/greenfield/scores/submit', $q)->assertSessionHas('ok');
            // locked for the teacher afterwards
            $this->post('/greenfield/scores/save', $q + ['score' => [$students[0]->id => ['ca1' => 15, 'ca2' => 15, 'exam' => 70]]]);
            $this->assertEquals(10, Result::where('student_id', $students[0]->id)->where('subject', $subject)->value('ca1') + 0);
            $this->logout();
        }

        $review = ['class' => 'JSS 1', 'session' => $session, 'term' => $term];

        // principal cannot approve before form teacher reviews
        $this->login('PRN-0001');
        $this->post('/greenfield/review/action', $review + ['do' => 'approve'])->assertForbidden();
        $this->logout();

        $this->login('FRM-0001');
        $this->post('/greenfield/review/action', $review + ['do' => 'mark_reviewed', 'comment' => 'Good class', 'comments' => [$students[0]->id => 'Keep it up']])->assertSessionHas('ok');
        $this->logout();

        // nothing visible to family yet
        $this->login('GRE/26/0001');
        $this->get('/greenfield/report/' . $students[0]->id)->assertOk()->assertSee('No published results');
        $this->logout();

        $this->login('PRN-0001');
        $this->post('/greenfield/review/action', $review + ['do' => 'approve'])->assertSessionHas('ok');
        $this->post('/greenfield/review/action', $review + ['do' => 'publish'])->assertForbidden();
        $this->logout();

        $this->login('ADM-0001');
        $this->post('/greenfield/review/action', $review + ['do' => 'publish'])->assertSessionHas('ok');
        $this->get('/greenfield/admin/annual')->assertOk();
        $this->post('/greenfield/admin/annual/compute', ['session' => $session])->assertSessionHas('ok');
        $this->get('/greenfield/analytics?class=JSS+1')->assertOk()->assertSee('Mathematics');
        $this->logout();

        // student sees the card, with a working verification code
        $this->login('GRE/26/0001');
        $res = $this->get('/greenfield/report/' . $students[0]->id)->assertOk()->assertSee('Mathematics')->assertSee('72');
        preg_match('/Verify: ([A-Z0-9]{4}-[A-Z0-9]{4})/', $res->getContent(), $m);
        $this->assertNotEmpty($m[1] ?? null);
        $this->get('/greenfield/report/' . $students[1]->id)->assertForbidden(); // not their sibling/classmate
        $this->logout();
        $this->get('/verify?code=' . $m[1])->assertOk()->assertSee('Authentic')->assertSee($students[0]->first_name);
        $this->get('/verify?code=ZZZZ-0000')->assertOk()->assertSee('No report card matches');

        // parent login (guardian phone) sees the same child
        $this->post('/greenfield/login', ['identifier' => '08030000001', 'pin' => '123456'])->assertRedirect('/greenfield/home');
        $this->get('/greenfield/me/results')->assertOk()->assertSee('Mathematics');
    }

    public function test_teacher_cannot_touch_unassigned_sheet(): void
    {
        $this->login('TCH-0001');
        $this->get('/greenfield/scores/sheet?class=JSS+1&subject=English+Language')->assertForbidden();
        $this->get('/greenfield/admin')->assertForbidden();
    }

    public function test_tenants_are_isolated(): void
    {
        $other = \App\Services\SchoolProvisioner::create(['name' => 'Other School']);
        $this->login('ADM-0001');
        // logged into greenfield — must not be treated as signed in to another school
        $this->get('/' . $other['school']->slug . '/home')->assertRedirect();
    }

    public function test_platform_admin_creates_school_from_request(): void
    {
        $this->post('/register-school', ['school_name' => 'New Hope College', 'contact_name' => 'Jo', 'email' => 'jo@new.test'])->assertSessionHas('ok');
        $this->get('/platform')->assertRedirect('/platform/login');
        $this->post('/platform/login', ['email' => 'admin@ata.test', 'password' => 'password'])->assertRedirect('/platform');
        $req = \App\Models\OnboardingRequest::first();
        $this->post("/platform/requests/{$req->id}/create-school")->assertSessionHas('ok');
        $this->assertDatabaseHas('schools', ['slug' => 'new-hope-college']);
    }
}
