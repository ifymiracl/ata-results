<?php

namespace App\Http\Controllers;

use App\Models\ClassReview;
use App\Models\Result;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentComment;
use App\Services\Audit;
use App\Services\Results;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    private function canReview(string $class): bool
    {
        $s = $this->staff();
        if (in_array($s->role, ['school_admin', 'principal'], true)) { return true; }
        return $s->role === 'form_teacher' && $s->assigned_class === $class;
    }

    public function index(Request $r)
    {
        $school = $this->school();
        $session = $r->input('session', $school->current_session); $term = $r->input('term', $school->current_term);
        $classes = collect($this->classesFor($this->staff()));
        if ($this->staff()->role === 'form_teacher') { $classes = collect([$this->staff()->assigned_class])->filter(); }
        $reviews = ClassReview::where(['school_id' => $school->id, 'session_label' => $session, 'term' => $term])->get()->keyBy('class_name');
        return view('school.review', ['classes' => $classes, 'reviews' => $reviews, 'session' => $session, 'term' => $term, 'terms' => $this->terms()]);
    }

    public function show(Request $r)
    {
        $school = $this->school();
        [$class, $session, $term] = $this->period($r);
        abort_unless($class !== '' && $this->canReview($class), 403);
        $review = Results::review($school->id, $class, $session, $term);
        $students = Student::where('school_id', $school->id)->where('class_name', $class)->active()->orderBy('first_name')->get();
        $subjects = Results::expectedSubjects($school->id, $class);
        $matrix = Result::where(['school_id' => $school->id, 'session_label' => $session, 'term' => $term])->whereIn('student_id', $students->pluck('id'))->get()->groupBy('student_id');
        $status = [];
        foreach ($subjects as $sub) {
            $ids = Results::eligible($school->id, $class, $sub)->pluck('id');
            $done = Result::where(['school_id' => $school->id, 'subject' => $sub, 'session_label' => $session, 'term' => $term])->whereIn('student_id', $ids)->whereIn('status', ['submitted', 'published'])->count();
            $status[$sub] = ['done' => $done, 'total' => $ids->count()];
        }
        $comments = StudentComment::where(['school_id' => $school->id, 'session_label' => $session, 'term' => $term])->whereIn('student_id', $students->pluck('id'))->get()->keyBy('student_id');
        return view('school.review-class', compact('class', 'session', 'term', 'review', 'students', 'subjects', 'matrix', 'status', 'comments'));
    }

    public function act(Request $r, School $school)
    {
        $d = $r->validate(['do' => 'required|in:return_subject,mark_reviewed,approve,return_to_form,publish', 'class' => 'required', 'session' => 'required', 'term' => 'required', 'subject' => 'nullable', 'comment' => 'nullable|max:1000', 'comments' => 'nullable|array']);
        $class = $d['class']; $session = $d['session']; $term = $d['term']; $role = $this->staff()->role;
        abort_unless($this->canReview($class), 403);
        $review = Results::review($school->id, $class, $session, $term);
        $status = $review->status ?? 'waiting';

        // per-student comments (form teacher fills teacher_comment, principal fills principal_comment)
        $saveComments = function () use ($d, $school, $role, $session, $term) {
            foreach ((array) ($d['comments'] ?? []) as $sid => $text) {
                $col = $role === 'principal' ? 'principal_comment' : 'teacher_comment';
                if (Student::where('school_id', $school->id)->whereKey($sid)->exists()) {
                    StudentComment::updateOrCreate(['school_id' => $school->id, 'student_id' => $sid, 'session_label' => $session, 'term' => $term], [$col => mb_substr((string) $text, 0, 500)]);
                }
            }
        };

        switch ($d['do']) {
            case 'return_subject':
                abort_unless(in_array($role, ['form_teacher', 'school_admin']) && in_array($status, ['waiting', 'submitted']), 403);
                $ids = Results::eligible($school->id, $class, $d['subject'] ?? '')->pluck('id');
                Result::where(['school_id' => $school->id, 'subject' => $d['subject'], 'session_label' => $session, 'term' => $term, 'status' => 'submitted'])->whereIn('student_id', $ids)->update(['status' => 'draft']);
                Results::setStatus($school->id, $class, $session, $term, 'waiting');
                $msg = "{$d['subject']} sent back to its teacher.";
                break;
            case 'mark_reviewed':
                abort_unless(in_array($role, ['form_teacher', 'school_admin']) && $status === 'submitted', 403, 'Every subject must be submitted first.');
                $saveComments();
                Results::setStatus($school->id, $class, $session, $term, 'reviewed', ['form_teacher_comment' => $d['comment'] ?? null, 'reviewed_at' => now()]);
                $msg = 'Class marked as reviewed and passed to the principal.';
                break;
            case 'approve':
                abort_unless(in_array($role, ['principal', 'school_admin']) && $status === 'reviewed', 403, 'The form teacher must review the class first.');
                $saveComments();
                Results::setStatus($school->id, $class, $session, $term, 'approved', ['principal_comment' => $d['comment'] ?? null]);
                $msg = 'Class approved. The school admin can now publish it.';
                break;
            case 'return_to_form':
                abort_unless(in_array($role, ['principal', 'school_admin']) && in_array($status, ['reviewed', 'approved']), 403);
                Results::setStatus($school->id, $class, $session, $term, 'submitted');
                $msg = 'Returned to the form teacher.';
                break;
            case 'publish':
                abort_unless($role === 'school_admin' && $status === 'approved', 403, 'Only an approved class can be published.');
                $ids = Student::where('school_id', $school->id)->where('class_name', $class)->active()->pluck('id');
                Result::where(['school_id' => $school->id, 'session_label' => $session, 'term' => $term, 'status' => 'submitted'])->whereIn('student_id', $ids)->update(['status' => 'published']);
                foreach ($ids as $sid) { Results::ensureCode($school->id, $sid, $session, $term); }
                Results::setStatus($school->id, $class, $session, $term, 'published', ['published_at' => now()]);
                $msg = 'Results published. Students and parents can now see them.';
                break;
        }
        Audit::by($this->me(), 'review.' . $d['do'], "$class / $session $term");
        return back()->with('ok', $msg);
    }
}
