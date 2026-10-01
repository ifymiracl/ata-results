@extends('layouts.app')
@section('title','Schools')
@section('content')
<h1>Schools</h1>
<div class="card tablewrap"><table><tr><th>School</th><th>Students</th><th>Staff</th><th>Trial / paid until</th><th>Manage</th></tr>
@foreach($schools as $s)<tr><td><a href="{{ route('school', $s) }}">{{ $s->name }}</a><br><span class="muted">/{{ $s->slug }} · {{ $s->code }}</span></td><td>{{ $s->students_count }}</td><td>{{ $s->staff_count }}</td>
<td class="muted">{{ $s->trial_ends_at?->format('j M Y') ?? '—' }} / {{ $s->subscription_paid_until?->format('j M Y') ?? '—' }}<br><span class="badge {{ $s->isUnlocked() ? 'ok' : 'bad' }}">{{ $s->isUnlocked() ? 'unlocked' : 'locked' }}</span></td>
<td><form method="post" action="{{ route('platform.schools.update', $s->id) }}" class="row">@csrf<select name="status"><option @selected($s->status==='active')>active</option><option @selected($s->status==='suspended')>suspended</option></select><input name="extend_days" type="number" min="0" placeholder="+ trial days" style="max-width:110px"><button class="btn sm">Save</button></form></td></tr>@endforeach</table></div>
@endsection
