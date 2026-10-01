@extends('layouts.app')
@section('content')
<section class="hero"><h1>{{ $school->name }}</h1>@if($school->motto)<p class="lede">{{ $school->motto }}</p>@endif<p class="muted">{{ $school->address }} {{ $school->state }}</p>
<p><a class="btn primary" href="{{ route('login', $school) }}">Sign in to the results portal</a> <a class="btn" href="{{ route('verify') }}">Verify a report card</a></p></section>
@if($notices->count())<h3>Notices</h3>@foreach($notices as $n)<div class="card"><b>{{ $n->pinned ? '📌 ' : '' }}{{ $n->title }}</b><p class="muted">{{ $n->created_at->format('j M Y') }}</p><p>{!! nl2br(e($n->body)) !!}</p></div>@endforeach @endif
@endsection
