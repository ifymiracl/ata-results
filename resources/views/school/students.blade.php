@extends('layouts.app')
@section('title','Students')
@section('content')
@include('partials.admintabs')@include('partials.credentials')
<div class="grid g2">
<form method="post" action="{{ route('admin.students.store', $school) }}" enctype="multipart/form-data" class="card">@csrf<h3>Add student</h3>
<div class="row"><div class="field"><label>First name</label><input name="first_name" required></div><div class="field"><label>Last name</label><input name="last_name" required></div></div>
<div class="row"><div class="field"><label>Middle name</label><input name="middle_name"></div><div class="field"><label>Gender</label><select name="gender"><option value="">—</option><option>M</option><option>F</option></select></div></div>
<div class="row"><div class="field"><label>Class</label><select name="class_name">@foreach($classes as $c)<option>{{ $c->name }}</option>@endforeach</select></div><div class="field"><label>Department</label><select name="department_id"><option value="">—</option>@foreach($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach</select></div></div>
<div class="row"><div class="field"><label>Guardian name</label><input name="guardian_name"></div><div class="field"><label>Guardian phone</label><input name="guardian_phone"></div><div class="field"><label>Guardian email</label><input type="email" name="guardian_email"></div></div>
<div class="row"><div class="field"><label>Date of birth</label><input type="date" name="date_of_birth"></div><div class="field"><label>Photo</label><input type="file" name="photo" accept="image/*"></div></div>
<button class="btn primary">Add &amp; generate PINs</button></form>
<div class="card"><h3>Import / export</h3><form method="post" action="{{ route('admin.students.import', $school) }}" enctype="multipart/form-data">@csrf<p class="muted">Columns: <code>first_name, last_name, class</code> (required), <code>middle_name, gender, admission_no, department, guardian_name, guardian_phone, guardian_email, date_of_birth</code>.</p><div class="field"><input type="file" name="file" accept=".csv,.txt" required></div><button class="btn">Import CSV</button></form><p class="mt"><a class="btn" href="{{ route('admin.students.export', $school) }}">Export all students (CSV)</a></p></div></div>
<form class="card row" method="get"><div><label>Search</label><input name="q" value="{{ request('q') }}"></div><div><label>Class</label><select name="class"><option value="">All</option>@foreach($classes as $c)<option @selected(request('class')===$c->name)>{{ $c->name }}</option>@endforeach</select></div><button class="btn">Filter</button></form>
<div class="card tablewrap"><table><tr><th>Admission</th><th>Name</th><th>Class</th><th>Guardian</th><th>Status</th><th></th></tr>
@foreach($students as $s)<tr><td>{{ $s->admission_no }}</td><td>{{ $s->full_name }}</td><td>{{ $s->class_name }}</td><td class="muted">{{ $s->guardian_name }} {{ $s->guardian_phone }}</td><td><span class="badge {{ $s->status==='active'?'ok':'' }}">{{ $s->status }}</span></td>
<td style="white-space:nowrap"><a class="btn sm" href="{{ route('idcard', [$school, $s->id]) }}">ID card</a>
<form method="post" action="{{ route('admin.students.reset', [$school, $s->id]) }}" style="display:inline">@csrf<input type="hidden" name="who" value="student"><button class="btn sm">Reset student PIN</button></form>
<form method="post" action="{{ route('admin.students.reset', [$school, $s->id]) }}" style="display:inline">@csrf<input type="hidden" name="who" value="parent"><button class="btn sm">Reset parent PIN</button></form></td></tr>@endforeach</table></div>
{{ $students->links() }}
@endsection
