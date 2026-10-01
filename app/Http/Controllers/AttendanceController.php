<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Student;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public const STATUSES = ['present', 'absent', 'late', 'excused'];

    private function allowedClasses(): array
    {
        $s = $this->staff();
        // Attendance is taken by the form teacher (and admin); subject teachers & principal view only.
        return $this->classesFor($s);
    }

    private function canMark(string $class): bool
    {
        $s = $this->staff();
        return $s->role === 'school_admin' || ($s->role === 'form_teacher' && $s->assigned_class === $class);
    }

    public function index(Request $r)
    {
        $school = $this->school();
        $classes = $this->allowedClasses();
        $class = $r->query('class', $this->staff()->assigned_class ?: ($classes[0] ?? ''));
        abort_unless($class === '' || in_array($class, $classes, true), 403);
        $date = $r->query('date', now()->toDateString());
        $students = $class ? Student::where('school_id', $school->id)->where('class_name', $class)->active()->orderBy('first_name')->get() : collect();
        $marks = Attendance::where('class_name', $class)->whereDate('date', $date)->whereIn('student_id', $students->pluck('id'))->get()->keyBy('student_id');
        $summary = Attendance::where('school_id', $school->id)->where('class_name', $class)->selectRaw('student_id, SUM(status IN ("present","late")) p, COUNT(*) t')->groupBy('student_id')->get()->keyBy('student_id');
        return view('school.attendance', ['classes' => $classes, 'class' => $class, 'date' => $date, 'students' => $students, 'marks' => $marks, 'summary' => $summary, 'canMark' => $class && $this->canMark($class)]);
    }

    public function save(Request $r)
    {
        $school = $this->school();
        $d = $r->validate(['class' => 'required', 'date' => 'required|date|before_or_equal:today', 'status' => 'required|array']);
        abort_unless($this->canMark($d['class']), 403);
        $valid = Student::where('school_id', $school->id)->where('class_name', $d['class'])->pluck('id')->all();
        foreach ($d['status'] as $sid => $st) {
            if (! in_array((int) $sid, $valid, true) || ! in_array($st, self::STATUSES, true)) { continue; }
            Attendance::updateOrCreate(['student_id' => $sid, 'date' => $d['date']], ['school_id' => $school->id, 'class_name' => $d['class'], 'status' => $st, 'note' => $r->input("note.$sid") ?: null, 'marked_by' => $this->staff()->id]);
        }
        return back()->with('ok', 'Attendance saved for ' . $d['date'] . '.');
    }
}
