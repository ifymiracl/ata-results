<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\ClassReview;
use App\Models\Result;
use App\Models\Student;
use App\Models\StudentFee;
use App\Services\Billing;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function home(Request $r)
    {
        $school = $this->school(); $me = $this->me(); $m = $me['model'];
        $session = $school->current_session; $term = $school->current_term;

        $aud = $me['type'] === 'staff' ? ['all', 'staff'] : ($me['type'] === 'student' ? ['all', 'students'] : ['all', 'parents']);
        $classFor = $me['type'] === 'staff' ? null : $m->class_name;
        $notices = Announcement::where('school_id', $school->id)->whereIn('audience', $aud)
            ->where(fn ($q) => $q->whereNull('class_name')->when($classFor, fn ($w) => $w->orWhere('class_name', $classFor)))
            ->orderByDesc('pinned')->latest()->limit(4)->get();

        if ($me['type'] !== 'staff') { return $this->family($m, $me['type'], $notices, $session, $term); }

        $data = ['notices' => $notices, 'session' => $session, 'term' => $term];
        if ($m->role === 'school_admin') {
            $data += [
                'students' => Student::where('school_id', $school->id)->active()->count(),
                'staffCount' => $school->staff()->where('status', 'active')->count(),
                'classCount' => $school->classes()->count(),
                'published' => ClassReview::where('school_id', $school->id)->where('session_label', $session)->where('term', $term)->where('status', 'published')->count(),
                'owed' => StudentFee::where('school_id', $school->id)->selectRaw('COALESCE(SUM(amount_due - amount_paid),0) as o')->value('o'),
                'unlocked' => $school->isUnlocked(), 'due' => Billing::amountDue($school),
            ];
        }
        // sheets this person is responsible for
        $data['sheets'] = $m->assignments()->orderBy('class_name')->get()->map(function ($a) use ($school, $session, $term) {
            $total = \App\Services\Results::eligible($school->id, $a->class_name, $a->subject)->count();
            $q = Result::where('school_id', $school->id)->where('subject', $a->subject)->where('session_label', $session)->where('term', $term)
                ->whereIn('student_id', \App\Services\Results::eligible($school->id, $a->class_name, $a->subject)->pluck('id'));
            $a->total = $total; $a->entered = (clone $q)->count(); $a->submitted = (clone $q)->whereIn('status', ['submitted', 'published'])->count();
            return $a;
        });
        $data['reviews'] = ClassReview::where('school_id', $school->id)->where('session_label', $session)->where('term', $term)
            ->when($m->role === 'form_teacher', fn ($q) => $q->where('class_name', $m->assigned_class))
            ->when($m->role === 'principal', fn ($q) => $q->whereIn('status', ['reviewed', 'approved']))->get();
        return view('school.dashboard', $data);
    }

    private function family(Student $s, string $type, $notices, string $session, string $term)
    {
        $published = Result::where('student_id', $s->id)->where('status', 'published')->where('session_label', $session)->where('term', $term)->get();
        $att = Attendance::where('student_id', $s->id)->get();
        $present = $att->whereIn('status', ['present', 'late'])->count();
        return view('school.family-home', [
            'student' => $s, 'type' => $type, 'notices' => $notices, 'published' => $published,
            'average' => $published->count() ? round($published->avg('total'), 1) : null,
            'attendanceRate' => $att->count() ? round($present / $att->count() * 100) : null,
            'owed' => StudentFee::where('student_id', $s->id)->selectRaw('COALESCE(SUM(amount_due - amount_paid),0) as o')->value('o'),
            'session' => $session, 'term' => $term,
        ]);
    }
}
