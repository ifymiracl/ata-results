<?php

namespace App\Http\Controllers;

use App\Models\OnboardingRequest;
use App\Models\ReportCode;
use App\Models\School;
use Illuminate\Http\Request;

class PublicController extends Controller
{
    public function home()
    {
        return view('public.home', ['schools' => School::where('status', 'active')->count()]);
    }

    public function find(Request $r)
    {
        $q = trim((string) $r->query('q'));
        $schools = $q === '' ? collect() : School::where('status', 'active')
            ->where(fn ($w) => $w->where('name', 'like', "%$q%")->orWhere('short_name', 'like', "%$q%")->orWhere('code', 'like', "%$q%")->orWhere('state', 'like', "%$q%"))
            ->limit(30)->get();
        return view('public.find', compact('q', 'schools'));
    }

    public function registerForm() { return view('public.register'); }

    public function register(Request $r)
    {
        $d = $r->validate([
            'school_name' => 'required|max:190', 'contact_name' => 'required|max:190', 'contact_role' => 'nullable|max:60',
            'student_count' => 'nullable|max:30', 'phone' => 'nullable|max:60', 'email' => 'required|email|max:190',
            'state' => 'nullable|max:100', 'country' => 'nullable|max:100', 'levels' => 'nullable|array', 'message' => 'nullable|max:2000',
        ]);
        $d['levels'] = implode(',', $d['levels'] ?? []);
        OnboardingRequest::create($d);
        return redirect()->route('register')->with('ok', 'Thanks! Your request is in. We will reach out to confirm and set your school up.');
    }

    public function verify(Request $r)
    {
        $code = strtoupper(trim((string) $r->query('code')));
        $found = null;
        if ($code !== '') {
            $rc = ReportCode::find($code);
            if ($rc) {
                $student = $rc->student; $school = School::find($rc->school_id);
                $found = ['rc' => $rc, 'student' => $student, 'school' => $school];
            }
        }
        return view('public.verify', compact('code', 'found'));
    }
}
