<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>@yield('title', 'ATA Results'){{ isset($school) ? ' · ' . ($school->short_name ?: $school->name) : '' }}</title>
<link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
<header class="top"><div class="wrap bar">
  <a class="wordmark" href="{{ isset($school) ? route('school', $school) : route('home') }}">{{ isset($school) ? ($school->short_name ?: $school->name) : 'ATA Results' }}<small>{{ isset($school) ? 'powered by ATA Results' : 'school records, signed off' }}</small></a>
  <nav class="main">
    @if(isset($school) && ($me ?? null))
      @php $t = $me['type']; $role = $t === 'staff' ? $me['model']->role : null; $r = fn($n) => request()->routeIs($n) ? 'on' : ''; @endphp
      <a class="{{ $r('home.school') }}" href="{{ route('home.school', $school) }}">Home</a>
      @if($t === 'staff')
        <a class="{{ $r('scores*') }}" href="{{ route('scores', $school) }}">Scores</a>
        <a class="{{ $r('attendance') }}" href="{{ route('attendance', $school) }}">Attendance</a>
        @if(in_array($role, ['form_teacher','principal','school_admin']))
          <a class="{{ $r('review*') }}" href="{{ route('review', $school) }}">Review</a>
          <a class="{{ $r('analytics') }}" href="{{ route('analytics', $school) }}">Analytics</a>
        @endif
        @if($role === 'school_admin')<a class="{{ $r('admin.*') }}" href="{{ route('admin.index', $school) }}">Admin</a>@endif
      @else
        <a href="{{ route('family.results', $school) }}">Results</a>
        <a href="{{ route('family.fees', $school) }}">Fees</a>
        <a href="{{ route('family.attendance', $school) }}">Attendance</a>
      @endif
      <a href="{{ route('announcements', $school) }}">Notices</a>
      <form method="post" action="{{ route('logout', $school) }}" style="display:inline">@csrf<button class="link">Sign out</button></form>
    @elseif(isset($school))
      <a href="{{ route('login', $school) }}">Sign in</a>
    @elseif(request()->routeIs('platform.*') && auth()->check())
      <a href="{{ route('platform.dashboard') }}">Overview</a><a href="{{ route('platform.requests') }}">Requests</a><a href="{{ route('platform.schools') }}">Schools</a><a href="{{ route('platform.settings') }}">Billing settings</a><a href="{{ route('platform.audit') }}">Audit</a>
      <form method="post" action="{{ route('platform.logout') }}" style="display:inline">@csrf<button class="link">Sign out</button></form>
    @else
      <a href="{{ route('find') }}">Find a school</a><a href="{{ route('verify') }}">Verify a result</a><a href="{{ route('register') }}">Register your school</a>
    @endif
  </nav>
</div></header>
<main><div class="wrap">
  @if(session('ok'))<div class="flash ok">{{ session('ok') }}</div>@endif
  @if(session('err'))<div class="flash err">{{ session('err') }}</div>@endif
  @if($errors->any())<div class="flash err">@foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif
  @yield('content')
</div></main>
<footer class="foot"><div class="wrap">© {{ date('Y') }} ATA Ventures · Every result passes through four hands before a parent sees it.</div></footer>
</body></html>
