<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\StudentFee;
use App\Services\ReportCards;
use Illuminate\Http\Request;

class FamilyController extends Controller
{
    public function results(Request $r)
    {
        $s = $this->me()['model']; $school = $this->school();
        $periods = ReportCards::periods($s);
        $session = $r->query('session', $periods[0][0] ?? $school->current_session); $term = $r->query('term', $periods[0][1] ?? $school->current_term);
        // trend: average per published term, oldest first
        $trend = collect($periods)->reverse()->map(fn ($p) => ['label' => substr($p[0], 2, 2) . '/' . substr($p[0], 7, 2) . ' ' . substr($p[1], 0, 1) . 'T', 'avg' => round(\App\Models\Result::where(['student_id' => $s->id, 'session_label' => $p[0], 'term' => $p[1], 'status' => 'published'])->avg('total'), 1)])->values();
        return view('school.family-results', ['student' => $s, 'periods' => $periods, 'session' => $session, 'term' => $term, 'card' => ReportCards::build($s, $school, $session, $term), 'trend' => $trend]);
    }

    public function fees()
    {
        $s = $this->me()['model'];
        return view('school.family-fees', ['student' => $s, 'fees' => StudentFee::where('student_id', $s->id)->with(['structure', 'student'])->latest()->get(), 'payments' => \App\Models\Payment::whereIn('student_fee_id', StudentFee::where('student_id', $s->id)->pluck('id'))->where('status', 'success')->latest()->get(), 'canPay' => $this->school()->paystack_public_key && $this->school()->paystack_secret_key]);
    }

    public function attendance()
    {
        $s = $this->me()['model'];
        $rows = Attendance::where('student_id', $s->id)->orderByDesc('date')->limit(90)->get();
        return view('school.family-attendance', ['student' => $s, 'rows' => $rows, 'present' => $rows->whereIn('status', ['present', 'late'])->count()]);
    }
}
