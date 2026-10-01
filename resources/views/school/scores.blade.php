@extends('layouts.app')
@section('title','Scores')
@section('content')
<h1>Score sheets</h1>
<form class="card" method="get">@include('partials.period')</form>
<div class="card tablewrap"><table><tr><th>Class</th><th>Subject</th><th>Entered</th><th>Status</th><th></th></tr>
@forelse($sheets as $s)<tr><td>{{ $s->class }}</td><td>{{ $s->subject }}</td><td>{{ $s->entered }}/{{ $s->total }}</td><td><span class="badge {{ $s->total && $s->submitted >= $s->total ? 'ok' : ($s->entered ? 'warn' : '') }}">{{ $s->total && $s->submitted >= $s->total ? 'submitted' : ($s->entered ? 'draft' : 'not started') }}</span></td><td><a class="btn sm" href="{{ route('scores.sheet', ['school'=>$school,'class'=>$s->class,'subject'=>$s->subject,'session'=>$session,'term'=>$term]) }}">Open</a></td></tr>
@empty<tr><td colspan="5" class="muted">No sheets assigned to you yet. Ask the school admin to assign subjects.</td></tr>@endforelse</table></div>
@endsection
