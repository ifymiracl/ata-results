@extends('layouts.app')
@section('title','School setup')
@section('content')
@include('partials.admintabs')
<form method="post" action="{{ route('admin.settings', $school) }}" enctype="multipart/form-data" class="card">@csrf<h3>School profile & term</h3>
<div class="grid g3">
<div class="field"><label>Name</label><input name="name" value="{{ old('name', $school->name) }}" required></div>
<div class="field"><label>Short name</label><input name="short_name" value="{{ old('short_name', $school->short_name) }}"></div>
<div class="field"><label>Web address (/…)</label><input name="slug" value="{{ old('slug', $school->slug) }}" required></div>
<div class="field"><label>Motto</label><input name="motto" value="{{ old('motto', $school->motto) }}"></div>
<div class="field"><label>Address</label><input name="address" value="{{ old('address', $school->address) }}"></div>
<div class="field"><label>State</label><input name="state" value="{{ old('state', $school->state) }}"></div>
<div class="field"><label>Country</label><input name="country" value="{{ old('country', $school->country) }}"></div>
<div class="field"><label>Phone</label><input name="phone" value="{{ old('phone', $school->phone) }}"></div>
<div class="field"><label>Email</label><input name="email" value="{{ old('email', $school->email) }}"></div>
<div class="field"><label>Current session</label><input name="current_session" value="{{ old('current_session', $school->current_session) }}" placeholder="2025/2026" required></div>
<div class="field"><label>Current term</label><select name="current_term">@foreach(['First Term','Second Term','Third Term'] as $t)<option @selected($school->current_term===$t)>{{ $t }}</option>@endforeach</select></div>
<div class="field"><label>Next term begins</label><input type="date" name="next_term_begins" value="{{ old('next_term_begins', $school->next_term_begins?->format('Y-m-d')) }}"></div>
<div class="field"><label>Promotion benchmark (%)</label><input name="promotion_benchmark" type="number" step="0.1" value="{{ old('promotion_benchmark', $school->promotion_benchmark) }}"></div>
<div class="field"><label>Logo</label><input type="file" name="logo" accept="image/*"></div></div>
<h3>Score weights (must total 100)</h3><div class="grid g3">
<div class="field"><label>CA 1</label><input name="w_ca1" type="number" step="0.5" value="{{ old('w_ca1', $weights['ca1']) }}"></div><div class="field"><label>CA 2</label><input name="w_ca2" type="number" step="0.5" value="{{ old('w_ca2', $weights['ca2']) }}"></div><div class="field"><label>Exam</label><input name="w_exam" type="number" step="0.5" value="{{ old('w_exam', $weights['exam']) }}"></div></div>
<h3>Grading scale</h3><div class="tablewrap"><table><tr><th>Min score</th><th>Grade</th><th>Remark</th></tr>
@foreach(array_pad($scale, count($scale) + 2, ['min'=>'','grade'=>'','remark'=>'']) as $i => $b)<tr><td><input name="scale[{{ $i }}][min]" value="{{ $b['min'] }}"></td><td><input name="scale[{{ $i }}][grade]" value="{{ $b['grade'] }}"></td><td><input name="scale[{{ $i }}][remark]" value="{{ $b['remark'] }}"></td></tr>@endforeach</table></div>
<h3 class="mt">Online fee payments (your own Paystack account)</h3><div class="grid g2"><div class="field"><label>Public key</label><input name="paystack_public_key" value="{{ $school->paystack_public_key }}"></div><div class="field"><label>Secret key {{ $school->paystack_secret_key ? '(saved — blank keeps it)' : '' }}</label><input type="password" name="paystack_secret_key" autocomplete="off"></div></div>
<p class="muted">Paystack webhook: <code>{{ route('webhooks.paystack') }}</code></p>
<button class="btn primary">Save settings</button></form>

<div class="grid g2">
<div class="card"><h3>Classes</h3><div class="tablewrap"><table><tr><th>Class</th><th>Capacity</th><th></th></tr>@foreach($classes as $c)<tr><td>{{ $c->name }}</td><td>{{ $c->capacity ?? '—' }}</td><td><form method="post" action="{{ route('admin.classes.delete', [$school, $c->id]) }}">@csrf<button class="btn sm danger">Remove</button></form></td></tr>@endforeach</table></div>
<form method="post" action="{{ route('admin.classes', $school) }}" class="row mt">@csrf<div><label>Class name</label><input name="name" placeholder="JSS 1 Gold" required></div><div><label>Capacity (for arm streaming)</label><input name="capacity" type="number" min="1"></div><button class="btn">Add / update</button></form></div>
<div class="card"><h3>Subjects</h3><div class="tablewrap"><table>@foreach($subjects as $s)<tr><td>{{ $s->name }}</td><td><span class="badge">{{ $s->is_core ? 'core' : 'elective' }}</span></td><td><form method="post" action="{{ route('admin.subjects.delete', [$school, $s->id]) }}">@csrf<button class="btn sm danger">Remove</button></form></td></tr>@endforeach</table></div>
<form method="post" action="{{ route('admin.subjects', $school) }}" class="row mt">@csrf<div><label>Subject</label><input name="name" required></div><div><label><input type="checkbox" name="is_core" value="1" checked> Core (everyone takes it)</label></div><button class="btn">Add</button></form></div></div>

<div class="card"><h3>Subjects each class takes</h3>
@foreach($classes as $c)<form method="post" action="{{ route('admin.classsubjects', $school) }}" class="mt">@csrf<input type="hidden" name="class_name" value="{{ $c->name }}"><b>{{ $c->name }}</b><div class="skills">@foreach($subjects as $s)<label style="font-weight:400"><input type="checkbox" name="subjects[]" value="{{ $s->name }}" @checked(in_array($s->name, ($plan[$c->name] ?? collect())->all()))> {{ $s->name }}</label>@endforeach</div><button class="btn sm">Save {{ $c->name }}</button></form>@endforeach</div>

<div class="card"><h3>Departments (for elective subjects)</h3>
@foreach($departments as $d)<form method="post" action="{{ route('admin.departments', $school) }}" class="mt">@csrf<input type="hidden" name="name" value="{{ $d->name }}"><b>{{ $d->name }}</b><div class="skills">@foreach($subjects->where('is_core', false) as $s)<label style="font-weight:400"><input type="checkbox" name="subjects[]" value="{{ $s->name }}" @checked($d->subjects->contains('subject', $s->name))> {{ $s->name }}</label>@endforeach</div><button class="btn sm">Save</button></form><form method="post" action="{{ route('admin.departments.delete', [$school, $d->id]) }}">@csrf<button class="btn sm danger">Remove {{ $d->name }}</button></form>@endforeach
<form method="post" action="{{ route('admin.departments', $school) }}" class="row mt">@csrf<div><label>New department</label><input name="name" placeholder="Science" required></div><button class="btn">Create</button></form></div>
@endsection
