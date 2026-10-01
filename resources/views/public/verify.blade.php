@extends('layouts.app')
@section('title','Verify a result')
@section('content')
<h1>Verify a report card</h1><p class="lede">Enter the verification code printed under the QR code on the report card.</p>
<form class="card row" method="get"><div><label>Code</label><input name="code" value="{{ $code }}" placeholder="ABCD-1234" autofocus></div><button class="btn primary">Verify</button></form>
@if($code !== '')
  @if($found)
    <div class="card"><span class="badge ok">Authentic</span>
    <h3 class="mt">{{ $found['student']->full_name }}</h3>
    <p>{{ $found['school']->name }} · {{ $found['student']->class_name }}<br>{{ $found['rc']->session_label }} · {{ $found['rc']->term }}<br><span class="muted">Issued {{ $found['rc']->created_at->format('j M Y') }}</span></p>
    <p class="muted">This confirms the report card was published by the school. Compare the scores on your copy against the school’s records if in doubt.</p></div>
  @else <div class="flash err">No report card matches that code. It may be mistyped or forged.</div>@endif
@endif
@endsection
