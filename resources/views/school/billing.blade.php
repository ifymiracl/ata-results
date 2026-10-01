@extends('layouts.app')
@section('title','Subscription')
@section('content')
@include('partials.admintabs')
<div class="card"><h3>ATA Results subscription</h3>
<p>Status: <span class="badge {{ $school->isUnlocked() ? 'ok' : 'bad' }}">{{ $school->isUnlocked() ? 'active' : 'locked' }}</span> · Plan: {{ $school->plan_name }} · Trial ends: {{ $school->trial_ends_at?->format('j M Y') ?? '—' }} · Paid until: {{ $school->subscription_paid_until?->format('j M Y') ?? '—' }}</p>
<p>{{ $count }} active students × ₦{{ number_format($price) }} = <b>₦{{ number_format($due) }}</b></p>
<form method="post" action="{{ route('admin.billing.pay', $school) }}">@csrf<button class="btn primary" @disabled(! $configured)>Pay with Paystack</button> @unless($configured)<span class="muted">Billing isn’t configured by the platform yet.</span>@endunless</form></div>
<div class="card tablewrap"><h3>Payment history</h3><table>@foreach($history as $h)<tr><td>{{ $h->created_at->format('j M Y') }}</td><td>{{ $h->student_count }} students</td><td>₦{{ number_format($h->amount) }}</td><td><span class="badge {{ $h->status==='success'?'ok':'warn' }}">{{ $h->status }}</span></td></tr>@endforeach</table></div>
@endsection
