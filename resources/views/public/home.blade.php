@extends('layouts.app')
@section('content')
<section class="hero">
  <h1>Every result passes through four hands before a parent sees it.</h1>
  <p class="lede">ATA Results is a records platform for schools running Nursery through SS3. A subject teacher enters scores, a form teacher checks the class, a principal signs off — and only then does the school admin publish.</p>
  <p><a class="btn primary" href="{{ route('register') }}">Register your school</a> <a class="btn" href="{{ route('find') }}">Find your school</a> <a class="btn" href="{{ route('verify') }}">Verify a report card</a></p>
</section>
<h2>How a result gets published</h2>
<div class="steps">
  <div><b>Subject teacher</b><p class="muted">Enters CA and exam scores per class — by hand or CSV — and submits the sheet.</p></div>
  <div><b>Form teacher</b><p class="muted">Checks every subject is in, returns gaps, rates conduct and skills, adds comments.</p></div>
  <div><b>Principal</b><p class="muted">Approves the class or sends it back to the form teacher.</p></div>
  <div><b>Publish</b><p class="muted">The admin publishes. Students and parents see it; the card gets a QR verification code.</p></div>
</div>
<h2 class="mt">Built for every role</h2>
<div class="grid g3">
  <div class="card"><h3>Schools</h3><p class="muted">Classes, subjects, departments, custom grading scale and score weights, fees with Paystack, attendance, notices, annual ranking and promotion.</p></div>
  <div class="card"><h3>Staff</h3><p class="muted">Each role sees only what the job needs. Score entry locks on submit; every action lands in an activity log.</p></div>
  <div class="card"><h3>Students &amp; parents</h3><p class="muted">PIN login, term-by-term results with a progress chart, attendance, fee balances and notices — on any phone.</p></div>
</div>
<p class="muted mt">{{ $schools }} school(s) on the platform.</p>
@endsection
