<?php

namespace App\Services;

use App\Models\AcademicAward;
use App\Models\AnnualResult;
use App\Models\Attendance;
use App\Models\ClassReview;
use App\Models\Result;
use App\Models\School;
use App\Models\SkillRating;
use App\Models\Student;
use App\Models\StudentComment;

class ReportCards
{
    public const SKILLS = ['Punctuality', 'Neatness', 'Politeness', 'Honesty', 'Leadership', 'Teamwork', 'Attentiveness', 'Creativity', 'Sports', 'Handwriting'];

    /** Only published results ever reach a report card. Returns null when nothing is published yet. */
    public static function build(Student $student, School $school, string $session, string $term): ?array
    {
        $rows = Result::where(['student_id' => $student->id, 'session_label' => $session, 'term' => $term, 'status' => 'published'])->orderBy('subject')->get();
        if ($rows->isEmpty()) { return null; }

        $pos = Results::classPositions($school->id, $student->class_name, $session, $term);
        $review = ClassReview::where(['school_id' => $school->id, 'class_name' => $student->class_name, 'session_label' => $session, 'term' => $term])->first();
        $att = Attendance::where('student_id', $student->id)->get();
        $subjectStats = Result::where(['school_id' => $school->id, 'session_label' => $session, 'term' => $term, 'status' => 'published'])
            ->whereIn('subject', $rows->pluck('subject'))->whereIn('student_id', Student::where('school_id', $school->id)->where('class_name', $student->class_name)->pluck('id'))
            ->selectRaw('subject, AVG(total) as avg, MAX(total) as high, MIN(total) as low')->groupBy('subject')->get()->keyBy('subject');
        $comments = StudentComment::where(['student_id' => $student->id, 'session_label' => $session, 'term' => $term])->first();
        $code = Results::ensureCode($school->id, $student->id, $session, $term);

        return [
            'rows' => $rows, 'stats' => $subjectStats, 'average' => round($rows->avg('total'), 1), 'total' => $rows->sum('total'),
            'position' => $pos[$student->id]['position'] ?? null, 'population' => count($pos),
            'review' => $review, 'comments' => $comments,
            'skills' => SkillRating::where(['student_id' => $student->id, 'session_label' => $session, 'term' => $term])->pluck('rating', 'skill'),
            'attendance' => ['days' => $att->count(), 'present' => $att->whereIn('status', ['present', 'late'])->count()],
            'annual' => $term === 'Third Term' ? AnnualResult::where(['student_id' => $student->id, 'session_label' => $session])->first() : null,
            'awards' => AcademicAward::where(['student_id' => $student->id, 'session_label' => $session])->get(),
            'code' => $code, 'verifyUrl' => route('verify', ['code' => $code]),
            'qr' => Qr::svg(route('verify', ['code' => $code]), 90),
            'session' => $session, 'term' => $term,
        ];
    }

    public static function periods(Student $student): array
    {
        return Result::where(['student_id' => $student->id, 'status' => 'published'])->select('session_label', 'term')->distinct()->orderByDesc('session_label')->orderByDesc('term')->get()->map(fn ($r) => [$r->session_label, $r->term])->all();
    }
}
