@extends('layouts.app')
@section('title',$class)
@section('content')
<h1>{{ $class }}</h1><div class="card tablewrap"><table><tr><th>Admission</th><th>Name</th><th>Gender</th><th></th></tr>@foreach($students as $s)<tr><td>{{ $s->admission_no }}</td><td>{{ $s->full_name }}</td><td>{{ $s->gender }}</td><td><a class="btn sm" href="{{ route('report', [$school, $s->id]) }}">Report card</a></td></tr>@endforeach</table></div>
@endsection
