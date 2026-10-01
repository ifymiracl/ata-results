@extends('layouts.app')
@section('title','Attendance')
@section('content')
<h1>Attendance</h1>
<form class="card row" method="get"><div><label>Class</label><select name="class">@foreach($classes as $c)<option @selected($class===$c)>{{ $c }}</option>@endforeach</select></div><div><label>Date</label><input type="date" name="date" value="{{ $date }}" max="{{ now()->toDateString() }}"></div><button class="btn">Load</button></form>
@if($class)
<form method="post" action="{{ route('attendance.save', $school) }}" class="card">@csrf<input type="hidden" name="class" value="{{ $class }}"><input type="hidden" name="date" value="{{ $date }}">
@unless($canMark)<p class="muted">View only — attendance is taken by the class’s form teacher.</p>@endunless
<div class="tablewrap"><table><tr><th>Student</th><th>Status</th><th>Note</th><th>Term to date</th></tr>
@foreach($students as $s)@php $m = $marks[$s->id] ?? null; $sm = $summary[$s->id] ?? null; @endphp<tr><td>{{ $s->full_name }}</td>
<td>@foreach(\App\Http\Controllers\AttendanceController::STATUSES as $st)<label style="display:inline;font-weight:400;margin-right:8px"><input type="radio" name="status[{{ $s->id }}]" value="{{ $st }}" @checked(($m->status ?? 'present') === $st) @disabled(! $canMark)> {{ $st }}</label>@endforeach</td>
<td><input name="note[{{ $s->id }}]" value="{{ $m->note ?? '' }}" @disabled(! $canMark)></td><td>{{ $sm ? round($sm->p / $sm->t * 100) . '%' : '—' }}</td></tr>@endforeach</table></div>
@if($canMark)<button class="btn primary mt">Save attendance</button>@endif</form>@endif
@endsection
