@extends('layouts.app')
@section('title','Report card')
@section('content')
<form class="card row noprint" method="get"><input type="hidden" name="_" value="1">
<div><label>Session</label><input name="session" value="{{ $session }}"></div><div><label>Term</label><select name="term">@foreach(['First Term','Second Term','Third Term'] as $t)<option @selected($term===$t)>{{ $t }}</option>@endforeach</select></div><button class="btn">Show</button><button type="button" class="btn primary" onclick="print()">Print / save PDF</button></form>
@if($card)@include('school._card')@else<div class="flash err">No published results for {{ $student->first_name }} in {{ $term }}, {{ $session }} yet.</div>@endif
@endsection
