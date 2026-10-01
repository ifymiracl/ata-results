@extends('layouts.app')
@section('title','Platform')
@section('content')
<h1>Platform overview</h1>
<div class="grid g4"><div class="stat"><b>{{ $schools }}</b><span>Schools</span></div><div class="stat"><b>{{ $students }}</b><span>Active students</span></div><div class="stat"><b>{{ $pending }}</b><span>Pending requests</span></div><div class="stat"><b>₦{{ number_format($revenue) }}</b><span>Subscription revenue</span></div></div>
<div class="card mt"><h3>Newest schools</h3><div class="tablewrap"><table><tr><th>School</th><th>Code</th><th>Plan</th><th>Status</th></tr>@foreach($recent as $s)<tr><td><a href="{{ route('school', $s) }}">{{ $s->name }}</a></td><td>{{ $s->code }}</td><td>{{ $s->plan_name }}</td><td><span class="badge {{ $s->status === 'active' ? 'ok' : 'bad' }}">{{ $s->status }}</span></td></tr>@endforeach</table></div></div>
@endsection
