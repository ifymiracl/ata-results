<?php

namespace App\Http\Controllers;

use App\Models\ClassSubject;
use App\Models\Result;
use App\Models\School;
use App\Models\StaffAssignment;
use App\Services\Audit;
use App\Services\Csv;
use App\Services\Results;
use Illuminate\Http\Request;

class ResultsController extends Controller
{
    private function owns(string $class, string $subject): bool
    {
        if ($this->isAdmin()) { return true; }
        return StaffAssignment::where(['staff_id' => $this->staff()->id, 'class_name' => $class, 'subject' => $subject])->exists();
    }

    public function index(Request $r)
    {
        $school = $this->school(); $staff = $this->staff();
        $session = $r->input('session', $school->current_session); $term = $r->input('term', $school->current_term);
        if ($this->isAdmin()) {
            $pairs = ClassSubject::where('school_id', $school->id)->orderBy('class_name')->orderBy('subject')->get(['class_name', 'subject']);
            if ($pairs->isEmpty()) { $pairs = StaffAssignment::where('school_id', $school->id)->get(['class_name', 'subject']); }
        } else {
            $pairs = $staff->assignments()->orderBy('class_name')->get(['class_name', 'subject']);
        }
        $sheets = $pairs->map(function ($p) use ($school, $session, $term) {
            $ids = Results::eligible($school->id, $p->class_name, $p->subject)->pluck('id');
            $q = Result::where(['school_id' => $school->id, 'subject' => $p->subject, 'session_label' => $session, 'term' => $term])->whereIn('student_id', $ids);
            return (object) ['class' => $p->class_name, 'subject' => $p->subject, 'total' => $ids->count(), 'entered' => (clone $q)->count(), 'submitted' => (clone $q)->whereIn('status', ['submitted', 'published'])->count()];
        });
        return view('school.scores', compact('sheets', 'session', 'term') + ['terms' => $this->terms()]);
    }

    public function sheet(Request $r)
    {
        $school = $this->school();
        $class = (string) $r->query('class'); $subject = (string) $r->query('subject');
        $session = $r->query('session', $school->current_session); $term = $r->query('term', $school->current_term);
        abort_unless($this->owns($class, $subject), 403);
        $students = Results::eligible($school->id, $class, $subject);
        $results = Result::where(['school_id' => $school->id, 'subject' => $subject, 'session_label' => $session, 'term' => $term])->whereIn('student_id', $students->pluck('id'))->get()->keyBy('student_id');
        $locked = $results->isNotEmpty() && $results->every(fn ($x) => $x->status !== 'draft');
        return view('school.sheet', compact('class', 'subject', 'session', 'term', 'students', 'results', 'locked') + ['weights' => $school->weights()]);
    }

    private function inputs(Request $r): array
    {
        $d = $r->validate(['class' => 'required', 'subject' => 'required', 'session' => 'required', 'term' => 'required']);
        abort_unless($this->owns($d['class'], $d['subject']), 403);
        return [$d['class'], $d['subject'], $d['session'], $d['term']];
    }

    private function writeScore(School $school, int $studentId, string $subject, string $session, string $term, array $v): void
    {
        $w = $school->weights();
        $ca1 = min($w['ca1'], max(0, (float) ($v['ca1'] ?? 0))); $ca2 = min($w['ca2'], max(0, (float) ($v['ca2'] ?? 0))); $exam = min($w['exam'], max(0, (float) ($v['exam'] ?? 0)));
        $total = $ca1 + $ca2 + $exam; $g = $school->gradeFor($total);
        $key = ['school_id' => $school->id, 'student_id' => $studentId, 'subject' => $subject, 'session_label' => $session, 'term' => $term];
        $existing = Result::where($key)->first();
        if ($existing && $existing->status !== 'draft' && ! $this->isAdmin()) { return; }
        Result::updateOrCreate($key, ['ca1' => $ca1, 'ca2' => $ca2, 'exam' => $exam, 'total' => $total, 'grade' => $g['grade'], 'remark' => $g['remark'], 'entered_by' => $this->staff()->id] + ($existing ? [] : ['status' => 'draft']));
    }

    public function save(Request $r)
    {
        [$class, $subject, $session, $term] = $this->inputs($r);
        $school = $this->school();
        $valid = Results::eligible($school->id, $class, $subject)->pluck('id')->all();
        foreach ((array) $r->input('score', []) as $sid => $v) {
            if (in_array((int) $sid, $valid, true)) { $this->writeScore($school, (int) $sid, $subject, $session, $term, $v); }
        }
        Audit::by($this->me(), 'scores.saved', "$class / $subject / $session $term");
        return back()->with('ok', 'Scores saved as draft.');
    }

    public function submit(Request $r)
    {
        [$class, $subject, $session, $term] = $this->inputs($r);
        $school = $this->school();
        $ids = Results::eligible($school->id, $class, $subject)->pluck('id');
        $drafts = Result::where(['school_id' => $school->id, 'subject' => $subject, 'session_label' => $session, 'term' => $term])->whereIn('student_id', $ids)->count();
        if ($drafts < $ids->count()) { return back()->with('err', 'Enter and save a score for every student before submitting (' . $drafts . ' of ' . $ids->count() . ' saved).'); }
        Result::where(['school_id' => $school->id, 'subject' => $subject, 'session_label' => $session, 'term' => $term, 'status' => 'draft'])->whereIn('student_id', $ids)->update(['status' => 'submitted']);
        Results::recompute($school->id, $class, $session, $term);
        Audit::by($this->me(), 'sheet.submitted', "$class / $subject / $session $term");
        return redirect()->route('scores', $school)->with('ok', "$subject ($class) submitted for review.");
    }

    public function template(Request $r)
    {
        $school = $this->school();
        $class = (string) $r->query('class'); $subject = (string) $r->query('subject');
        abort_unless($this->owns($class, $subject), 403);
        $rows = Results::eligible($school->id, $class, $subject)->map(fn ($s) => [$s->admission_no, $s->full_name, '', '', '']);
        return Csv::download("scores-$class-$subject.csv", ['admission_no', 'name', 'ca1', 'ca2', 'exam'], $rows);
    }

    public function import(Request $r)
    {
        [$class, $subject, $session, $term] = $this->inputs($r);
        $r->validate(['file' => 'required|file|mimes:csv,txt|max:2048']);
        $school = $this->school();
        $students = Results::eligible($school->id, $class, $subject)->keyBy('admission_no');
        $n = 0;
        foreach (Csv::read($r->file('file')) as $row) {
            $s = $students[$row['admission_no'] ?? ''] ?? null;
            if ($s) { $this->writeScore($school, $s->id, $subject, $session, $term, $row); $n++; }
        }
        return back()->with('ok', "$n scores imported as draft. Review them, then submit.");
    }
}
