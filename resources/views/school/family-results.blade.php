@extends('layouts.app')
@section('title','Results')
@section('content')
<h1>Results</h1>
<form class="card row" method="get"><div><label>Session</label><input name="session" value="{{ $session }}"></div><div><label>Term</label><select name="term">@foreach(['First Term','Second Term','Third Term'] as $t)<option @selected($term===$t)>{{ $t }}</option>@endforeach</select></div><button class="btn">Show</button></form>
@if($trend->count() > 1)@php $max = max(1, $trend->max('avg')); @endphp<div class="card"><h3>Progress by term</h3><div class="chart">@foreach($trend as $t)<div><i style="height:{{ $t['avg'] / $max * 120 }}px"></i>{{ $t['label'] }}<br><b>{{ $t['avg'] }}</b></div>@endforeach</div></div>@endif
@if($card)@include('school._card', ['scale'=>$school->scale()])<p class="noprint"><button class="btn primary" onclick="print()">Print / save PDF</button></p>@else<div class="flash err">Nothing has been published for this term yet.</div>@endif
@endsection
