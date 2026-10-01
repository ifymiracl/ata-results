@extends('layouts.app')
@section('title','Attendance')
@section('content')
<h1>Attendance — {{ $student->first_name }}</h1><p class="muted">Present or late on {{ $present }} of the last {{ $rows->count() }} marked days.</p>
<div class="card tablewrap"><table>@forelse($rows as $r)<tr><td>{{ $r->date->format('D j M Y') }}</td><td><span class="badge {{ in_array($r->status,['present'])?'ok':($r->status==='absent'?'bad':'warn') }}">{{ $r->status }}</span></td><td class="muted">{{ $r->note }}</td></tr>@empty<tr><td class="muted">No attendance recorded yet.</td></tr>@endforelse</table></div>
@endsection
