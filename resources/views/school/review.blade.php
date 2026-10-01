@extends('layouts.app')
@section('title','Review')
@section('content')
<h1>Class review</h1><form class="card" method="get">@include('partials.period')</form>
@php $cls = ['waiting'=>'','submitted'=>'warn','reviewed'=>'warn','approved'=>'ok','published'=>'ok']; @endphp
<div class="grid g3">@foreach($classes as $c)@php $st = $reviews[$c]->status ?? 'waiting'; @endphp
<div class="card"><h3>{{ $c }}</h3><span class="badge {{ $cls[$st] }}">{{ $st }}</span><p class="mt"><a class="btn sm primary" href="{{ route('review.class', ['school'=>$school,'class'=>$c,'session'=>$session,'term'=>$term]) }}">Open</a> <a class="btn sm" href="{{ route('skills', ['school'=>$school,'class'=>$c,'session'=>$session,'term'=>$term]) }}">Skills</a></p></div>@endforeach</div>
@endsection
