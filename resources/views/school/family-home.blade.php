@extends('layouts.app')
@section('title','Home')
@section('content')
<h1>{{ $student->full_name }}</h1><p class="muted">{{ $student->class_name }} · {{ $student->admission_no }} · {{ $type === 'parent' ? 'Parent view' : 'Student view' }}</p>
<div class="grid g4"><div class="stat"><b>{{ $average ?? '—' }}</b><span>Average, {{ $term }}</span></div><div class="stat"><b>{{ $published->count() }}</b><span>Subjects published</span></div><div class="stat"><b>{{ $attendanceRate !== null ? $attendanceRate . '%' : '—' }}</b><span>Attendance</span></div><div class="stat"><b>₦{{ number_format($owed) }}</b><span>Fees outstanding</span></div></div>
<p class="mt"><a class="btn primary" href="{{ route('family.results', $school) }}">View results</a> <a class="btn" href="{{ route('report', ['school'=>$school,'student'=>$student->id]) }}">Report card</a> <a class="btn" href="{{ route('family.fees', $school) }}">Pay fees</a></p>
@include('school._notices')
@endsection
