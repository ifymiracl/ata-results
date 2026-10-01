@extends('layouts.app')
@section('title','Find a school')
@section('content')
<h1>Find your school</h1>
<form class="card row" method="get"><div><label>School name, code or state</label><input name="q" value="{{ $q }}" autofocus></div><button class="btn primary">Search</button></form>
@foreach($schools as $s)<div class="card"><h3><a href="{{ route('school', $s) }}">{{ $s->name }}</a></h3><p class="muted">{{ $s->state }} {{ $s->country }} · {{ $s->code }}</p><a class="btn sm" href="{{ route('login', $s) }}">Sign in</a></div>@endforeach
@if($q !== '' && $schools->isEmpty())<p class="muted">No school matched “{{ $q }}”.</p>@endif
@endsection
