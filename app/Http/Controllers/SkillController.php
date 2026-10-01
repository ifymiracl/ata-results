<?php

namespace App\Http\Controllers;

use App\Models\SkillRating;
use App\Models\Student;
use App\Services\ReportCards;
use Illuminate\Http\Request;

class SkillController extends Controller
{
    private function check(string $class)
    {
        abort_unless(in_array($class, $this->classesFor($this->staff()), true), 403);
    }

    public function index(Request $r)
    {
        $school = $this->school();
        [$class, $session, $term] = $this->period($r);
        $class = $class ?: ($this->classesFor($this->staff())[0] ?? '');
        $this->check($class);
        $students = Student::where('school_id', $school->id)->where('class_name', $class)->active()->orderBy('first_name')->get();
        $ratings = SkillRating::where(['school_id' => $school->id, 'session_label' => $session, 'term' => $term])->whereIn('student_id', $students->pluck('id'))->get()->groupBy('student_id')->map->pluck('rating', 'skill');
        return view('school.skills', ['class' => $class, 'session' => $session, 'term' => $term, 'students' => $students, 'ratings' => $ratings, 'skills' => ReportCards::SKILLS, 'classes' => $this->classesFor($this->staff()), 'terms' => $this->terms()]);
    }

    public function save(Request $r)
    {
        $school = $this->school();
        $d = $r->validate(['class' => 'required', 'session' => 'required', 'term' => 'required', 'rating' => 'nullable|array']);
        $this->check($d['class']);
        $valid = Student::where('school_id', $school->id)->where('class_name', $d['class'])->pluck('id')->all();
        foreach ((array) $d['rating'] as $sid => $skills) {
            if (! in_array((int) $sid, $valid, true)) { continue; }
            foreach ($skills as $skill => $v) {
                if (! in_array($skill, ReportCards::SKILLS, true) || ! in_array((int) $v, [1, 2, 3, 4, 5], true)) { continue; }
                SkillRating::updateOrCreate(['student_id' => $sid, 'session_label' => $d['session'], 'term' => $d['term'], 'skill' => $skill], ['school_id' => $school->id, 'rating' => (int) $v]);
            }
        }
        return back()->with('ok', 'Ratings saved.');
    }
}
