<?php

namespace App\Services;

use App\Models\ClassReview;
use App\Models\DepartmentSubject;
use App\Models\ReportCode;
use App\Models\Result;
use App\Models\Student;
use App\Models\StaffAssignment;
use App\Models\Subject;
use Illuminate\Support\Str;

class Results
{
    /** Everyone for a core subject; only matching departments for an elective. */
    public static function eligible(int $schoolId, string $class, string $subject)
    {
        $q = Student::where('school_id', $schoolId)->where('class_name', $class)->active()->orderBy('first_name')->orderBy('last_name');
        $subj = Subject::where('school_id', $schoolId)->where('name', $subject)->first();
        if ($subj && ! $subj->is_core) {
            $q->whereIn('department_id', DepartmentSubject::where('subject', $subject)->pluck('department_id'));
        }
        return $q->get();
    }

    public static function expectedSubjects(int $schoolId, string $class): array
    {
        return StaffAssignment::where('school_id', $schoolId)->where('class_name', $class)->distinct()->pluck('subject')->all();
    }

    public static function review(int $schoolId, string $class, string $session, string $term): ?ClassReview
    {
        return ClassReview::where(['school_id' => $schoolId, 'class_name' => $class, 'session_label' => $session, 'term' => $term])->first();
    }

    public static function setStatus(int $schoolId, string $class, string $session, string $term, string $status, array $extra = []): void
    {
        ClassReview::updateOrCreate(
            ['school_id' => $schoolId, 'class_name' => $class, 'session_label' => $session, 'term' => $term],
            ['status' => $status] + $extra
        );
    }

    /** A class moves from "waiting" to "submitted" once every assigned subject sheet is fully submitted. */
    public static function recompute(int $schoolId, string $class, string $session, string $term): void
    {
        $existing = self::review($schoolId, $class, $session, $term);
        if ($existing && in_array($existing->status, ['reviewed', 'approved', 'published'], true)) { return; }
        $subjects = self::expectedSubjects($schoolId, $class);
        if (! $subjects) { self::setStatus($schoolId, $class, $session, $term, 'waiting'); return; }
        foreach ($subjects as $subject) {
            $ids = self::eligible($schoolId, $class, $subject)->pluck('id');
            if ($ids->isEmpty()) { continue; }
            $done = Result::where(['school_id' => $schoolId, 'subject' => $subject, 'session_label' => $session, 'term' => $term])
                ->whereIn('status', ['submitted', 'published'])->whereIn('student_id', $ids)->count();
            if ($done < $ids->count()) { self::setStatus($schoolId, $class, $session, $term, 'waiting'); return; }
        }
        self::setStatus($schoolId, $class, $session, $term, 'submitted');
    }

    public static function ensureCode(int $schoolId, int $studentId, string $session, string $term): string
    {
        $existing = ReportCode::where(['student_id' => $studentId, 'session_label' => $session, 'term' => $term])->value('code');
        if ($existing) { return $existing; }
        do { $code = strtoupper(Str::random(4) . '-' . Str::random(4)); } while (ReportCode::find($code));
        ReportCode::create(['code' => $code, 'school_id' => $schoolId, 'student_id' => $studentId, 'session_label' => $session, 'term' => $term]);
        return $code;
    }

    /** Position of each student in the class for a term, by average of published totals. Ties share a rank. */
    public static function classPositions(int $schoolId, string $class, string $session, string $term): array
    {
        $ids = Student::where('school_id', $schoolId)->where('class_name', $class)->active()->pluck('id');
        $avgs = Result::where(['school_id' => $schoolId, 'session_label' => $session, 'term' => $term, 'status' => 'published'])
            ->whereIn('student_id', $ids)->selectRaw('student_id, AVG(total) as a')->groupBy('student_id')->pluck('a', 'student_id')->map(fn ($v) => round((float) $v, 2))->all();
        arsort($avgs);
        $pos = []; $rank = 0; $prev = null; $i = 0;
        foreach ($avgs as $sid => $a) { $i++; if ($a !== $prev) { $rank = $i; $prev = $a; } $pos[$sid] = ['position' => $rank, 'average' => $a]; }
        return $pos;
    }

    public static function ordinal(int $n): string
    {
        $s = ['th', 'st', 'nd', 'rd']; $v = $n % 100;
        return $n . ($s[($v - 20) % 10] ?? $s[$v] ?? $s[0]);
    }
}
