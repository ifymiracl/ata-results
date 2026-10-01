<?php

namespace App\Http\Controllers;

use App\Models\ReportCode;
use App\Models\School;
use App\Models\Student;
use App\Services\ReportCards;
use Illuminate\Http\Request;

class ReportCardController extends Controller
{
    /** Who may see this student's card: staff in the school, the student themself, or their linked parent. */
    private function allowed(Student $student): bool
    {
        $me = $this->me();
        if ($student->school_id !== $this->school()->id) { return false; }
        if ($me['type'] === 'staff') {
            $role = $me['model']->role;
            return in_array($role, ['school_admin', 'principal'], true) || in_array($student->class_name, $this->classesFor($me['model']), true);
        }
        return $me['model']->id === $student->id;
    }

    public function show(Request $r, School $school, $student)
    {
        $student = Student::findOrFail($student);
        abort_unless($this->allowed($student), 403);
        $session = $r->query('session', $school->current_session); $term = $r->query('term', $school->current_term);
        $card = ReportCards::build($student, $school, $session, $term);
        return view('school.report', compact('student', 'card', 'session', 'term') + ['periods' => ReportCards::periods($student), 'scale' => $school->scale()]);
    }

    /** Whole-class printable batch, only for classes the viewer reviews. */
    public function printClass(Request $r)
    {
        $school = $this->school();
        [$class, $session, $term] = $this->period($r);
        abort_unless(in_array($class, $this->classesFor($this->staff()), true), 403);
        $cards = Student::where('school_id', $school->id)->where('class_name', $class)->active()->orderBy('first_name')->get()
            ->map(fn ($s) => ['student' => $s, 'card' => ReportCards::build($s, $school, $session, $term)])->filter(fn ($x) => $x['card']);
        return view('school.report-batch', ['cards' => $cards, 'class' => $class, 'session' => $session, 'term' => $term, 'scale' => $school->scale()]);
    }

    /** Anyone holding the printed code/link can see a short verification — no scores, no login. */
    public function publicVerify(School $school, string $code)
    {
        $rc = ReportCode::where('school_id', $school->id)->find(strtoupper($code));
        abort_unless($rc, 404);
        return redirect()->route('verify', ['code' => $rc->code]);
    }
}
