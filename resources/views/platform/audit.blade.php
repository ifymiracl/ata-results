@extends('layouts.app')
@section('title','Audit')
@section('content')
<h1>Audit log</h1>
<div class="card tablewrap"><table><tr><th>When</th><th>Actor</th><th>Action</th><th>Detail</th><th>IP</th></tr>@foreach($logs as $l)<tr><td>{{ $l->created_at->format('j M H:i') }}</td><td>{{ $l->actor }}</td><td>{{ $l->action }}</td><td>{{ $l->detail }}</td><td class="muted">{{ $l->ip }}</td></tr>@endforeach</table></div>
@endsection
