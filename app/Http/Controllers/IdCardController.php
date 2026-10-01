<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Services\Qr;

class IdCardController extends Controller
{
    public function show($school, $student)
    {
        $s = Student::where('school_id', $this->school()->id)->findOrFail($student);
        $me = $this->me();
        abort_unless($me['type'] === 'staff' ? in_array($me['model']->role, ['school_admin', 'principal', 'form_teacher']) : $me['model']->id === $s->id, 403);
        return view('school.idcard', ['student' => $s, 'qr' => Qr::svg(route('school', $s->school_id ? \App\Models\School::find($s->school_id) : null) . '?id=' . urlencode($s->admission_no), 70)]);
    }
}
