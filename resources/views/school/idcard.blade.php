@extends('layouts.app')
@section('title','ID card')
@section('content')
<div class="idcard"><div class="hd">{{ $school->name }}</div><div class="bd">
<div class="ph">@if($student->photo_path)<img src="{{ asset('storage/' . $student->photo_path) }}" alt="" style="width:100%;height:100%;object-fit:cover">@else{{ strtoupper(substr($student->first_name,0,1)) }}@endif</div>
<div><b style="font-size:17px">{{ $student->full_name }}</b><br>{{ $student->class_name }}<br><small>ID: {{ $student->admission_no }}</small><br><small>{{ $student->date_of_birth?->format('j M Y') }}</small></div></div>
<div style="padding:0 14px 12px;display:flex;justify-content:space-between;align-items:end"><small>{{ $school->phone }}<br>Session {{ $school->current_session }}</small>{!! $qr !!}</div></div>
<p class="noprint" style="text-align:center;margin-top:20px"><button class="btn primary" onclick="print()">Print</button></p>
@endsection
