@extends('layouts.app')
@section('title','Analytics')
@section('content')
<h1>Class analytics</h1><form class="card" method="get">@include('partials.period')</form>
@if(! $n)<p class="muted">No submitted or published scores for this class and term yet.</p>@else
<div class="grid g2"><div class="card"><h3>Grade distribution</h3>@php $max = max(1, $dist->max()); @endphp<div class="chart">@foreach($dist as $g => $c)<div><i style="height:{{ $c / $max * 120 }}px"></i>{{ $g }}<br><b>{{ $c }}</b></div>@endforeach</div></div>
<div class="card"><h3>Top performers</h3>@foreach($top as $id => $a)<p>{{ $loop->iteration }}. {{ $names[$id]->full_name ?? '' }} — <b>{{ $a }}%</b></p>@endforeach<h3 class="mt">Needs support (below {{ $school->promotion_benchmark + 0 }}%)</h3>@forelse($atRisk as $id => $a)<p>{{ $names[$id]->full_name ?? '' }} — <span class="badge bad">{{ $a }}%</span></p>@empty<p class="muted">Nobody is below the benchmark.</p>@endforelse</div></div>
<div class="card tablewrap"><h3>By subject</h3><table><tr><th>Subject</th><th>Average</th><th>Highest</th><th>Lowest</th><th>Pass rate</th><th></th></tr>@foreach($subjects as $s => $x)<tr><td>{{ $s }}</td><td>{{ $x['avg'] }}</td><td>{{ $x['high'] + 0 }}</td><td>{{ $x['low'] + 0 }}</td><td>{{ $x['pass'] }}%</td><td style="width:25%"><div class="bar-track"><div class="bar-fill" style="width:{{ min(100,$x['avg']) }}%"></div></div></td></tr>@endforeach</table></div>@endif
@endsection
