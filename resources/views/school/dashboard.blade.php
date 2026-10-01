@extends('layouts.app')
@section('title','Home')
@section('content')
@php $m = $me['model']; @endphp
<h1>Hello, {{ explode(' ', $m->name)[0] }}</h1><p class="muted">{{ $m->roleLabel() }} · {{ $session }} · {{ $term }}</p>
@if($m->role === 'school_admin')
  @unless($unlocked)<div class="flash err">Your trial/subscription has lapsed — staff, students and parents are locked out. <a href="{{ route('admin.billing', $school) }}">Renew now</a> (₦{{ number_format($due) }}).</div>@endunless
  <div class="grid g4"><div class="stat"><b>{{ $students }}</b><span>Active students</span></div><div class="stat"><b>{{ $staffCount }}</b><span>Staff</span></div><div class="stat"><b>{{ $published }}/{{ $classCount }}</b><span>Classes published this term</span></div><div class="stat"><b>₦{{ number_format($owed) }}</b><span>Fees outstanding</span></div></div>
  <div class="mt"><a class="btn" href="{{ route('admin.index', $school) }}">School setup</a> <a class="btn" href="{{ route('review', $school) }}">Review & publish</a> <a class="btn" href="{{ route('analytics', $school) }}">Analytics</a></div>
@endif
@if($sheets->count())<div class="card mt"><h3>My score sheets</h3><div class="tablewrap"><table><tr><th>Class</th><th>Subject</th><th>Progress</th><th></th></tr>
@foreach($sheets as $s)<tr><td>{{ $s->class_name }}</td><td>{{ $s->subject }}</td><td>{{ $s->entered }}/{{ $s->total }} entered · <span class="badge {{ $s->total && $s->submitted >= $s->total ? 'ok' : 'warn' }}">{{ $s->total && $s->submitted >= $s->total ? 'submitted' : 'open' }}</span></td><td><a class="btn sm" href="{{ route('scores.sheet', ['school'=>$school,'class'=>$s->class_name,'subject'=>$s->subject]) }}">Open</a></td></tr>@endforeach</table></div></div>@endif
@if($reviews->count())<div class="card"><h3>Class reviews</h3>@foreach($reviews as $rv)<p><a href="{{ route('review.class', ['school'=>$school,'class'=>$rv->class_name]) }}">{{ $rv->class_name }}</a> — <span class="badge">{{ $rv->status }}</span></p>@endforeach</div>@endif
@include('school._notices')
@endsection
