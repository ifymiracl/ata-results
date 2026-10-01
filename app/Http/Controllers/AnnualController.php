<?php

namespace App\Http\Controllers;

use App\Models\AcademicAward;
use App\Models\AnnualResult;
use App\Models\School;
use App\Services\Audit;
use App\Services\Promotion;
use Illuminate\Http\Request;

class AnnualController extends Controller
{
    public function index(Request $r, School $school)
    {
        $session = $r->query('session', $school->current_session);
        $rows = AnnualResult::where(['school_id' => $school->id, 'session_label' => $session])->with('student')->orderBy('level_position')->get()->sortBy(fn ($x) => $x->student?->class_name . str_pad((string) $x->class_position, 4, '0', STR_PAD_LEFT));
        $awards = AcademicAward::where(['school_id' => $school->id, 'session_label' => $session])->with('student')->get();
        return view('school.annual', compact('rows', 'awards', 'session'));
    }

    public function compute(Request $r, School $school)
    {
        $session = $r->validate(['session' => 'required|regex:/^\d{4}\/\d{4}$/'])['session'];
        $n = Promotion::compute($school, $session);
        Audit::by($this->me(), 'annual.computed', "$session ($n students)");
        return redirect()->route('admin.annual', ['school' => $school, 'session' => $session])->with($n ? 'ok' : 'err', $n ? "Computed annual results for $n students." : 'No published results found for that session yet.');
    }

    public function apply(Request $r, School $school)
    {
        $session = $r->validate(['session' => 'required'])['session'];
        $x = Promotion::apply($school, $session);
        Audit::by($this->me(), 'annual.applied', "$session " . json_encode($x));
        return back()->with('ok', "Applied: {$x['moved']} promoted, {$x['graduated']} graduated, {$x['repeat']} repeating.");
    }
}
