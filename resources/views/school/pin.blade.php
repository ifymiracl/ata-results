@extends('layouts.app')
@section('title','Choose a PIN')
@section('content')
<div class="loginbox card"><h2>Choose your own PIN</h2><p class="muted">Your temporary PIN was set by the school. Pick a new 6-digit PIN that only you know.</p>
<form method="post" action="{{ route('pin.save', $school) }}">@csrf
<div class="field"><label>New PIN</label><input name="pin" inputmode="numeric" maxlength="6" required></div>
<div class="field"><label>Repeat PIN</label><input name="pin_confirmation" inputmode="numeric" maxlength="6" required></div>
<button class="btn primary">Save PIN</button></form></div>
@endsection
