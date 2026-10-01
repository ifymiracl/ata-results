<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PagesSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_admin_and_staff_page_renders(): void
    {
        $this->seed(DatabaseSeeder::class);
        $sid = Student::first()->id;
        $this->post('/greenfield/login', ['identifier' => 'ADM-0001', 'pin' => '123456']);

        foreach (['home', 'announcements', 'scores', 'attendance', 'review', 'review/class?class=JSS+1', 'review/skills?class=JSS+1', 'analytics', 'admin', 'admin/staff', 'admin/students', 'admin/students?q=Chidi', 'admin/annual', 'admin/fees', 'admin/billing', 'admin/audit', "id-card/$sid", "report/$sid", 'scores/sheet?class=JSS+1&subject=Mathematics', 'classes/JSS 1/students', 'admin/students/export'] as $path) {
            $this->get('/greenfield/' . $path)->assertOk();
        }
    }

    public function test_admin_can_set_up_and_bill_things(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->post('/greenfield/login', ['identifier' => 'ADM-0001', 'pin' => '123456']);
        $this->post('/greenfield/admin/classes', ['name' => 'JSS 2', 'capacity' => 30])->assertSessionHas('ok');
        $this->post('/greenfield/admin/fees', ['label' => 'Tuition', 'session_label' => '2026/2027', 'term' => 'First Term', 'amount' => 50000])->assertSessionHas('ok');
        $fee = \App\Models\StudentFee::first();
        $this->post('/greenfield/admin/fees/record', ['student_fee_id' => $fee->id, 'amount' => 20000, 'method' => 'cash'])->assertSessionHas('ok');
        $this->assertEquals(20000, $fee->fresh()->amount_paid + 0);
        $this->assertSame('partial', $fee->fresh()->status);
        $this->post('/greenfield/admin/announcements', ['title' => 'PTA', 'body' => 'Saturday', 'audience' => 'all'])->assertSessionHas('ok');
        $this->post('/greenfield/admin/students', ['first_name' => 'New', 'last_name' => 'Kid', 'class_name' => 'JSS 1', 'guardian_email' => 'k@p.test'])->assertSessionHas('ok');
        $this->get('/greenfield/admin/fees')->assertOk()->assertSee('Tuition');

        // attendance by admin, then visible to the child's family
        $s = Student::first();
        $this->post('/greenfield/attendance', ['class' => 'JSS 1', 'date' => now()->toDateString(), 'status' => [$s->id => 'late']])->assertSessionHas('ok');
        $this->post('/greenfield/logout');
        $this->post('/greenfield/login', ['identifier' => $s->admission_no, 'pin' => '123456']);
        foreach (['home', 'me/results', 'me/fees', 'me/attendance', 'announcements'] as $p) { $this->get('/greenfield/' . $p)->assertOk(); }
        $this->get('/greenfield/admin')->assertForbidden();
    }

    public function test_lapsed_school_locks_non_admins(): void
    {
        $this->seed(DatabaseSeeder::class);
        \App\Models\School::first()->update(['trial_ends_at' => now()->subDay()]);
        $this->post('/greenfield/login', ['identifier' => 'TCH-0001', 'pin' => '123456'])->assertSessionHasErrors('identifier');
        $this->post('/greenfield/login', ['identifier' => 'ADM-0001', 'pin' => '123456'])->assertRedirect('/greenfield/home');
    }

    public function test_platform_pages_render(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->actingAs(User::where('is_platform_admin', true)->first());
        foreach (['', '/requests', '/schools', '/settings', '/audit'] as $p) { $this->get('/platform' . $p)->assertOk(); }
        $this->post('/platform/settings', ['price' => 150, 'trial' => 30, 'days' => 120, 'paystack_public' => 'pk', 'paystack_secret' => 'sk'])->assertSessionHas('ok');
        $this->assertSame('sk', \App\Models\Setting::get('paystack_secret'));
    }
}
