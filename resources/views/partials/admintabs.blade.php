@php $on = fn($n) => request()->routeIs($n) ? 'on' : ''; @endphp
<div class="tabs">
  <a class="{{ $on('admin.index') }}" href="{{ route('admin.index', $school) }}">Setup</a>
  <a class="{{ $on('admin.staff') }}" href="{{ route('admin.staff', $school) }}">Staff</a>
  <a class="{{ $on('admin.students') }}" href="{{ route('admin.students', $school) }}">Students</a>
  <a class="{{ $on('admin.annual') }}" href="{{ route('admin.annual', $school) }}">Annual & promotion</a>
  <a class="{{ $on('admin.fees') }}" href="{{ route('admin.fees', $school) }}">Fees</a>
  <a class="{{ $on('admin.billing') }}" href="{{ route('admin.billing', $school) }}">Subscription</a>
  <a class="{{ $on('admin.audit') }}" href="{{ route('admin.audit', $school) }}">Activity log</a>
</div>
