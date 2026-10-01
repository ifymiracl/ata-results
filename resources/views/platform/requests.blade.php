@extends('layouts.app')
@section('title','Requests')
@section('content')
<h1>School requests</h1>
@forelse($requests as $q)
<div class="card"><div class="row"><div><h3>{{ $q->school_name }}</h3><p class="muted">{{ $q->contact_name }} ({{ $q->contact_role }}) · {{ $q->email }} · {{ $q->phone }}<br>{{ $q->state }} {{ $q->country }} · {{ $q->student_count }} students · {{ $q->levels }}</p>@if($q->message)<p>{{ $q->message }}</p>@endif</div><span class="badge {{ ['approved'=>'ok','rejected'=>'bad'][$q->status] ?? 'warn' }}" style="flex:0">{{ $q->status }}</span></div>
@if($q->created_school_id)<p class="muted">School created.</p>@else
<form method="post" action="{{ route('platform.requests.update', $q) }}" class="row">@csrf
<div><label>Status</label><select name="status">@foreach(['pending','contacted','approved','rejected'] as $s)<option @selected($q->status===$s)>{{ $s }}</option>@endforeach</select></div>
<div><label>Notes</label><input name="admin_notes" value="{{ $q->admin_notes }}"></div><button class="btn">Save</button></form>
<form method="post" action="{{ route('platform.requests.create', $q) }}" class="mt">@csrf<button class="btn primary">Create school &amp; admin login</button></form>@endif</div>
@empty<p class="muted">No requests yet.</p>@endforelse
@endsection
