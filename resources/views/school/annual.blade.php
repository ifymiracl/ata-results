@extends('layouts.app')
@section('title','Annual results')
@section('content')
@include('partials.admintabs')
<div class="card"><h3>Compute annual results</h3><p class="muted">Averages every published term of a session, ranks students in class and level, and recommends promotion at your benchmark ({{ $school->promotion_benchmark + 0 }}%). Safe to re-run — it replaces the previous computation until you apply it.</p>
<form method="post" action="{{ route('admin.annual.compute', $school) }}" class="row">@csrf<div><label>Session</label><input name="session" value="{{ $session }}" required></div><button class="btn primary">Compute</button></form></div>
@if($rows->count())
<form method="post" action="{{ route('admin.annual.apply', $school) }}" class="card" onsubmit="return confirm('Move students to their new classes? Graduates are marked graduated.')">@csrf<input type="hidden" name="session" value="{{ $session }}"><h3>Apply promotion</h3><p class="muted">Moves promoted students to the recommended class and marks graduating students. Do this once the session is closed.</p><button class="btn">Apply to {{ $session }}</button></form>
@if($awards->count())<div class="card"><h3>Awards</h3>@foreach($awards as $a)<p>🏆 <b>{{ $a->student?->full_name }}</b> — {{ $a->award_type==='overall_best' ? 'Overall best, ' : 'Best in ' . $a->subject . ', ' }}{{ $a->level_label }}</p>@endforeach</div>@endif
<div class="card tablewrap"><table><tr><th>Student</th><th>Class</th><th>Average</th><th>Class pos.</th><th>Level pos.</th><th>Recommendation</th></tr>
@foreach($rows as $r)<tr><td>{{ $r->student?->full_name }}</td><td>{{ $r->student?->class_name }}</td><td>{{ $r->annual_average }}%</td><td>{{ $r->class_position }}/{{ $r->class_population }}</td><td>{{ $r->level_position }}/{{ $r->level_population }}</td><td><span class="badge {{ $r->recommended_action==='repeat'?'bad':'ok' }}">{{ ucfirst($r->recommended_action) }}{{ $r->recommended_class ? ' → ' . $r->recommended_class : '' }}</span> @if($r->applied)<span class="badge">applied</span>@endif</td></tr>@endforeach</table></div>@endif
@endsection
