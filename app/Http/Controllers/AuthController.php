<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\School;
use App\Services\Audit;
use App\Services\Portal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    public function landing(School $school)
    {
        if (Portal::current($school)) { return redirect()->route('home.school', $school); }
        $notices = Announcement::where('school_id', $school->id)->where('audience', 'all')->whereNull('class_name')->orderByDesc('pinned')->latest()->limit(5)->get();
        return view('school.landing', compact('notices'));
    }

    public function loginForm(School $school)
    {
        return Portal::current($school) ? redirect()->route('home.school', $school) : view('school.login');
    }

    public function login(Request $r, School $school)
    {
        $d = $r->validate(['identifier' => 'required|max:190', 'pin' => 'required|digits:6']);
        if (Portal::throttled($school, $d['identifier'], $r->ip())) {
            return back()->withErrors(['identifier' => 'Too many attempts. Try again in a few minutes.'])->onlyInput('identifier');
        }
        $subject = Portal::attempt($school, $d['identifier'], $d['pin'], $r->ip());
        if (! $subject) { return back()->withErrors(['identifier' => 'Those details do not match. Check your ID and PIN.'])->onlyInput('identifier'); }

        $isAdmin = $subject['type'] === 'staff' && $subject['model']->role === 'school_admin';
        if (! $school->isUnlocked() && ! $isAdmin) {
            return back()->withErrors(['identifier' => 'This school’s subscription has lapsed. Please ask the school administrator.'])->onlyInput('identifier');
        }
        Portal::login($school, $subject['type'], $subject['model']->id);
        Audit::by(['type' => $subject['type'], 'model' => $subject['model']], 'login');
        return redirect()->route('home.school', $school);
    }

    public function logout(School $school)
    {
        Portal::logout();
        return redirect()->route('school', $school)->with('ok', 'Signed out.');
    }

    public function pinForm() { return view('school.pin'); }

    public function pinSave(Request $r, School $school)
    {
        $d = $r->validate(['pin' => 'required|digits:6|confirmed']);
        $me = $this->me();
        Portal::setPin($me['type'], $me['model']->id, $d['pin']);
        Audit::by($me, 'pin.changed');
        return redirect()->route('home.school', $school)->with('ok', 'PIN updated.');
    }

    /** Always answers the same way, so it can't be used to discover who has an account. */
    public function forgot(Request $r, School $school)
    {
        $id = trim((string) $r->input('identifier'));
        $subject = Portal::findSubject($school, $id);
        if ($subject) {
            $m = $subject['model'];
            $email = $subject['type'] === 'staff' ? $m->email : $m->guardian_email;
            if ($email) {
                $pin = Portal::generatePin();
                Portal::setPin($subject['type'], $m->id, $pin);
                $m->refresh()->forceFill([$subject['type'] === 'parent' ? 'parent_must_change_pin' : 'must_change_pin' => true])->save();
                Mail::raw("Your temporary {$school->name} PIN is $pin. You will be asked to choose a new one when you sign in.", fn ($mm) => $mm->to($email)->subject('Your temporary PIN'));
                Audit::log($school->id, $id, 'pin.forgot');
            }
        }
        return back()->with('ok', 'If that account has an email on file, a new temporary PIN has been sent. Otherwise ask your school administrator to reset it.');
    }
}
