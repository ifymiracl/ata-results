@extends('layouts.app')
@section('title','Billing settings')
@section('content')
<h1>Billing settings</h1>
<form method="post" action="{{ route('platform.settings.save') }}" class="card">@csrf
<div class="grid g2"><div class="field"><label>Price per active student (₦)</label><input name="price" type="number" step="0.01" value="{{ $price }}"></div>
<div class="field"><label>Free trial (days)</label><input name="trial" type="number" value="{{ $trial }}"></div>
<div class="field"><label>Days of access per payment</label><input name="days" type="number" value="{{ $days }}"></div><div></div>
<div class="field"><label>Platform Paystack public key</label><input name="paystack_public" value="{{ $public }}"></div>
<div class="field"><label>Platform Paystack secret key {{ $hasSecret ? '(saved — leave blank to keep)' : '' }}</label><input type="password" name="paystack_secret" autocomplete="off"></div></div>
<p class="muted">Webhook URL for Paystack: <code>{{ route('webhooks.paystack') }}</code></p>
<button class="btn primary">Save</button></form>
@endsection
