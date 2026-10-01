@extends('layouts.app')
@section('title','Platform sign in')
@section('content')
<div class="loginbox card"><h2>Platform console</h2>
<form method="post" action="{{ route('platform.login') }}">@csrf
<div class="field"><label>Email</label><input type="email" name="email" value="{{ old('email') }}" required autofocus></div>
<div class="field"><label>Password</label><input type="password" name="password" required></div>
<button class="btn primary">Sign in</button></form></div>
@endsection
