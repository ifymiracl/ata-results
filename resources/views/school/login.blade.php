@extends('layouts.app')
@section('title','Sign in')
@section('content')
<div class="loginbox"><div class="card"><h2>Sign in</h2>
<p class="muted">Staff: staff ID, phone or email · Students: admission number · Parents: guardian phone or email.</p>
<form method="post" action="{{ route('login.post', $school) }}">@csrf
<div class="field"><label>ID, phone or email</label><input name="identifier" value="{{ old('identifier', request('id')) }}" required autofocus autocomplete="username"></div>
<div class="field"><label>6-digit PIN</label><input name="pin" inputmode="numeric" maxlength="6" pattern="\d{6}" required autocomplete="current-password"></div>
<button class="btn primary">Sign in</button></form></div>
<div class="card"><h3>Forgot your PIN?</h3><form method="post" action="{{ route('forgot', $school) }}" class="row">@csrf<div><input name="identifier" placeholder="Your ID, phone or email"></div><button class="btn">Email me a new PIN</button></form></div></div>
@endsection
