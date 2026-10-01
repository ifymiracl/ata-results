{{-- expects $session, $term, optional $class / $classes --}}
<div class="row">
  @isset($classes)<div><label>Class</label><select name="class">@foreach($classes as $c)<option @selected(($class ?? '') === $c)>{{ $c }}</option>@endforeach</select></div>@endisset
  <div><label>Session</label><input name="session" value="{{ $session }}" pattern="\d{4}/\d{4}"></div>
  <div><label>Term</label><select name="term">@foreach(['First Term','Second Term','Third Term'] as $t)<option @selected($term === $t)>{{ $t }}</option>@endforeach</select></div>
  <button class="btn primary">Go</button>
</div>
