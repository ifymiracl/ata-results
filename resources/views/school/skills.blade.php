@extends('layouts.app')
@section('title','Conduct & skills')
@section('content')
<h1>Conduct &amp; skills</h1><p class="muted">Rate each student 1 (poor) – 5 (excellent). These appear on the report card.</p>
<form class="card" method="get">@include('partials.period')</form>
<form method="post" action="{{ route('skills.save', $school) }}" class="card">@csrf<input type="hidden" name="class" value="{{ $class }}"><input type="hidden" name="session" value="{{ $session }}"><input type="hidden" name="term" value="{{ $term }}">
<div class="tablewrap"><table><tr><th>Student</th>@foreach($skills as $sk)<th>{{ $sk }}</th>@endforeach</tr>
@foreach($students as $s)<tr><td style="white-space:nowrap">{{ $s->full_name }}</td>@foreach($skills as $sk)<td><select name="rating[{{ $s->id }}][{{ $sk }}]" style="min-width:56px"><option value="">–</option>@for($i=5;$i>=1;$i--)<option @selected(($ratings[$s->id][$sk] ?? null) == $i)>{{ $i }}</option>@endfor</select></td>@endforeach</tr>@endforeach</table></div>
<button class="btn primary mt">Save ratings</button></form>
@endsection
