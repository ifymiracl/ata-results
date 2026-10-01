@extends('layouts.app')
@section('title','Register your school')
@section('content')
<h1>Start with your school</h1><p class="lede">Submit your details and the platform team will reach out to confirm and set your school up.</p>
<form method="post" action="{{ route('register.store') }}" class="card">@csrf
<div class="grid g2">
<div class="field"><label>School name *</label><input name="school_name" value="{{ old('school_name') }}" required></div>
<div class="field"><label>Your name *</label><input name="contact_name" value="{{ old('contact_name') }}" required></div>
<div class="field"><label>Your role</label><input name="contact_role" value="{{ old('contact_role') }}" placeholder="Proprietor, Principal…"></div>
<div class="field"><label>Email *</label><input type="email" name="email" value="{{ old('email') }}" required></div>
<div class="field"><label>Phone</label><input name="phone" value="{{ old('phone') }}"></div>
<div class="field"><label>Approx. students</label><select name="student_count">@foreach(['under 100','100-300','300-700','700+'] as $o)<option>{{ $o }}</option>@endforeach</select></div>
<div class="field"><label>State</label><input name="state" value="{{ old('state') }}"></div>
<div class="field"><label>Country</label><input name="country" value="{{ old('country', 'Nigeria') }}"></div>
</div>
<div class="field"><label>Levels</label>@foreach(['nursery'=>'Nursery','primary'=>'Primary','jss'=>'Junior Secondary','ss'=>'Senior Secondary'] as $k=>$v)<label style="display:inline-block;margin-right:14px"><input type="checkbox" name="levels[]" value="{{ $k }}" @checked(in_array($k, old('levels', ['primary','jss','ss'])))> {{ $v }}</label>@endforeach</div>
<div class="field"><label>Anything we should know?</label><textarea name="message" rows="3">{{ old('message') }}</textarea></div>
<button class="btn primary">Send request</button></form>
@endsection
