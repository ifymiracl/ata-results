@extends('layouts.app')
@section('title','Fees')
@section('content')
<h1>Fees — {{ $student->first_name }}</h1>
<div class="card tablewrap"><table><tr><th>Fee</th><th>Term</th><th>Due</th><th>Paid</th><th>Balance</th><th></th></tr>
@forelse($fees as $f)<tr><td>{{ $f->structure?->label }}</td><td>{{ $f->structure?->session_label }} {{ $f->structure?->term }}</td><td>₦{{ number_format($f->amount_due) }}</td><td>₦{{ number_format($f->amount_paid) }}</td><td><b>₦{{ number_format($f->balance) }}</b></td>
<td>@if($f->balance > 0)@if($canPay)<form method="post" action="{{ route('family.pay', [$school, $f->id]) }}">@csrf<button class="btn sm primary">Pay online</button></form>@else<span class="muted">Pay at school office</span>@endif @else<span class="badge ok">paid</span>@endif</td></tr>@empty<tr><td colspan="6" class="muted">No fees billed.</td></tr>@endforelse</table></div>
@if($payments->count())<div class="card tablewrap"><h3>Receipts</h3><table>@foreach($payments as $p)<tr><td>{{ $p->created_at->format('j M Y') }}</td><td>{{ $p->reference }}</td><td>₦{{ number_format($p->amount) }}</td><td>{{ $p->method }}</td></tr>@endforeach</table></div>@endif
@endsection
