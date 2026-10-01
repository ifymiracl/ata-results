@extends('layouts.app')
@section('title','Class report cards')
@section('content')
<div class="card noprint"><h3>{{ $class }} · {{ $term }}, {{ $session }}</h3><p>{{ $cards->count() }} report cards. <button class="btn primary" onclick="print()">Print all</button></p></div>
@foreach($cards as $x)@include('school._card', ['student'=>$x['student'],'card'=>$x['card']])@endforeach
@endsection
