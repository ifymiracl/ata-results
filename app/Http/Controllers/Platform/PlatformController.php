<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\OnboardingRequest;
use App\Models\School;
use App\Models\Setting;
use App\Models\Student;
use App\Models\SubscriptionPayment;
use App\Services\Audit;
use App\Services\SchoolProvisioner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class PlatformController extends Controller
{
    public function loginForm() { return view('platform.login'); }

    public function login(Request $r)
    {
        $c = $r->validate(['email' => 'required|email', 'password' => 'required']);
        if (Auth::attempt($c + ['is_platform_admin' => true])) {
            $r->session()->regenerate();
            return redirect()->route('platform.dashboard');
        }
        return back()->withErrors(['email' => 'Those details do not match a platform admin.'])->onlyInput('email');
    }

    public function logout(Request $r)
    {
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();
        return redirect()->route('home');
    }

    public function dashboard()
    {
        return view('platform.dashboard', [
            'schools' => School::count(), 'students' => Student::where('status', 'active')->count(),
            'pending' => OnboardingRequest::where('status', 'pending')->count(),
            'revenue' => SubscriptionPayment::where('status', 'success')->sum('amount'),
            'recent' => School::latest()->limit(8)->get(),
        ]);
    }

    public function requests()
    {
        return view('platform.requests', ['requests' => OnboardingRequest::latest()->get()]);
    }

    public function updateRequest(Request $r, OnboardingRequest $req)
    {
        $req->update($r->validate(['status' => 'required|in:pending,contacted,approved,rejected', 'admin_notes' => 'nullable|max:2000']));
        return back()->with('ok', 'Request updated.');
    }

    public function createSchool(Request $r, OnboardingRequest $req)
    {
        abort_if($req->created_school_id, 422, 'School already created.');
        $levels = array_values(array_filter(explode(',', (string) $req->levels))) ?: ['primary', 'jss', 'ss'];
        $made = SchoolProvisioner::create([
            'name' => $req->school_name, 'state' => $req->state, 'country' => $req->country,
            'phone' => $req->phone, 'email' => $req->email, 'admin_name' => $req->contact_name,
        ], $levels);
        $req->update(['status' => 'approved', 'created_school_id' => $made['school']->id]);
        Audit::log($made['school']->id, 'platform', 'school.created', $made['school']->name);
        $url = route('login', $made['school']);
        if ($req->email) {
            Mail::raw("Your school {$made['school']->name} is ready on ATA Results.\n\nSign in: $url\nUser ID: ADM-0001 (or this email)\nTemporary PIN: {$made['pin']}\n\nYou will be asked to choose a new PIN on first sign-in.", fn ($m) => $m->to($req->email)->subject('Your ATA Results school is ready'));
        }
        return redirect()->route('platform.requests')->with('ok', "School created: {$made['school']->name}. Admin login ADM-0001, temporary PIN {$made['pin']} (shown once) — sign-in link: $url");
    }

    public function schools()
    {
        $schools = School::withCount(['students' => fn ($q) => $q->where('status', 'active'), 'staff'])->latest()->get();
        return view('platform.schools', compact('schools'));
    }

    public function updateSchool(Request $r, $id)
    {
        $s = School::findOrFail($id);
        $d = $r->validate(['status' => 'required|in:active,suspended', 'extend_days' => 'nullable|integer|min:0|max:3650', 'plan_name' => 'nullable|max:60']);
        $s->status = $d['status'];
        if ($r->filled('plan_name')) { $s->plan_name = $d['plan_name']; }
        if (! empty($d['extend_days'])) {
            $from = ($s->trial_ends_at && $s->trial_ends_at->isFuture()) ? $s->trial_ends_at : now();
            $s->trial_ends_at = $from->copy()->addDays($d['extend_days']);
        }
        $s->save();
        Audit::log($s->id, 'platform', 'school.updated', $s->status);
        return back()->with('ok', 'School updated.');
    }

    public function settings()
    {
        return view('platform.settings', [
            'price' => Setting::get('price_per_student', 100), 'public' => Setting::get('paystack_public'),
            'hasSecret' => (bool) Setting::get('paystack_secret'), 'trial' => Setting::get('trial_days', 30), 'days' => Setting::get('subscription_days', 120),
        ]);
    }

    public function saveSettings(Request $r)
    {
        $d = $r->validate(['price' => 'required|numeric|min:0', 'paystack_public' => 'nullable|max:190', 'paystack_secret' => 'nullable|max:190', 'trial' => 'required|integer|min:0', 'days' => 'required|integer|min:1']);
        Setting::put('price_per_student', $d['price']); Setting::put('trial_days', $d['trial']); Setting::put('subscription_days', $d['days']);
        Setting::put('paystack_public', $d['paystack_public'] ?? '');
        if (! empty($d['paystack_secret'])) { Setting::put('paystack_secret', $d['paystack_secret']); }
        return back()->with('ok', 'Settings saved.');
    }

    public function audit()
    {
        return view('platform.audit', ['logs' => AuditLog::with([])->latest()->limit(300)->get()]);
    }
}
