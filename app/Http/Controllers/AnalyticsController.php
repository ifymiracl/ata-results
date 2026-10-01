<?php

namespace App\Http\Controllers;

use App\Models\Result;
use App\Models\Student;
use App\Services\Csv;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function index(Request $r)
    {
        $school = $this->school();
        $classes = $this->classesFor($this->staff());
        [$class, $session, $term] = $this->period($r);
        $class = $class ?: ($classes[0] ?? '');
        abort_unless($class === '' || in_array($class, $classes, true), 403);
        $ids = Student::where('school_id', $school->id)->where('class_name', $class)->active()->pluck('id');
        $base = Result::where(['school_id' => $school->id, 'session_label' => $session, 'term' => $term])->whereIn('student_id', $ids)->whereIn('status', ['submitted', 'published']);
        $rows = (clone $base)->get();
        $dist = $rows->groupBy('grade')->map->count()->sortKeys();
        $subjects = $rows->groupBy('subject')->map(fn ($g) => ['avg' => round($g->avg('total'), 1), 'high' => $g->max('total'), 'low' => $g->min('total'), 'pass' => round($g->where('total', '>=', $school->promotion_benchmark)->count() / max(1, $g->count()) * 100)])->sortByDesc('avg');
        $top = $rows->groupBy('student_id')->map(fn ($g) => round($g->avg('total'), 1))->sortDesc()->take(5);
        $atRisk = $rows->groupBy('student_id')->map(fn ($g) => round($g->avg('total'), 1))->filter(fn ($a) => $a < $school->promotion_benchmark)->sort()->take(8);
        $names = Student::whereIn('id', $top->keys()->merge($atRisk->keys()))->get()->keyBy('id');
        return view('school.analytics', compact('classes', 'class', 'session', 'term', 'dist', 'subjects', 'top', 'atRisk', 'names') + ['terms' => $this->terms(), 'n' => $rows->count()]);
    }

    public function exportResults(Request $r)
    {
        $school = $this->school();
        [$class, $session, $term] = $this->period($r);
        abort_unless(in_array($class, $this->classesFor($this->staff()), true), 403);
        $ids = Student::where('school_id', $school->id)->where('class_name', $class)->pluck('id');
        $rows = Result::where(['school_id' => $school->id, 'session_label' => $session, 'term' => $term])->whereIn('student_id', $ids)->with('student')->orderBy('subject')->get()
            ->map(fn ($x) => [$x->student->admission_no, $x->student->full_name, $x->subject, $x->ca1, $x->ca2, $x->exam, $x->total, $x->grade, $x->status]);
        return Csv::download("results-$class-" . str_replace('/', '-', $session) . '-' . $term . '.csv', ['admission_no', 'name', 'subject', 'ca1', 'ca2', 'exam', 'total', 'grade', 'status'], $rows);
    }
}
