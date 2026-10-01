@extends('layouts.app')
@section('title',"Review $class")
@section('content')
@php $st = $review->status ?? 'waiting'; $role = $me['model']->role; $q = ['school'=>$school,'class'=>$class,'session'=>$session,'term'=>$term]; @endphp
<p><a href="{{ route('review', $school) }}">← All classes</a></p>
<h1>{{ $class }} <small class="muted">{{ $session }} · {{ $term }}</small> <span class="badge">{{ $st }}</span></h1>
<div class="card"><h3>Subject sheets</h3><div class="tablewrap"><table><tr><th>Subject</th><th>Submitted</th><th></th></tr>
@forelse($status as $sub => $x)<tr><td>{{ $sub }}</td><td><span class="badge {{ $x['done'] >= $x['total'] ? 'ok' : 'warn' }}">{{ $x['done'] }}/{{ $x['total'] }}</span></td><td>
@if(in_array($role, ['form_teacher','school_admin']) && $x['done'] > 0 && in_array($st, ['waiting','submitted']))<form method="post" action="{{ route('review.act', $school) }}">@csrf<input type="hidden" name="do" value="return_subject"><input type="hidden" name="class" value="{{ $class }}"><input type="hidden" name="session" value="{{ $session }}"><input type="hidden" name="term" value="{{ $term }}"><input type="hidden" name="subject" value="{{ $sub }}"><button class="btn sm">Return to teacher</button></form>@endif</td></tr>
@empty<tr><td colspan="3" class="muted">No teachers are assigned to this class yet.</td></tr>@endforelse</table></div></div>

<div class="card"><h3>Scores overview</h3><div class="tablewrap"><table><tr><th>Student</th>@foreach($subjects as $sub)<th>{{ \Illuminate\Support\Str::limit($sub, 10) }}</th>@endforeach<th>Avg</th></tr>
@foreach($students as $s)@php $rs = ($matrix[$s->id] ?? collect())->keyBy('subject'); @endphp<tr><td>{{ $s->full_name }}</td>@foreach($subjects as $sub)<td>{{ $rs[$sub]->total ?? '—' }}</td>@endforeach<td><b>{{ $rs->count() ? round($rs->avg('total'),1) : '—' }}</b></td></tr>@endforeach</table></div>
<p class="mt"><a class="btn sm" href="{{ route('export.results', $q) }}">Export CSV</a> <a class="btn sm" href="{{ route('skills', $q) }}">Conduct & skills</a> @if($st==='published')<a class="btn sm" href="{{ route('report.class', $q) }}">Print all report cards</a>@endif</p></div>

@if(in_array($st, ['submitted','reviewed']) || ($st==='approved'))
<form method="post" action="{{ route('review.act', $school) }}" class="card">@csrf
<input type="hidden" name="class" value="{{ $class }}"><input type="hidden" name="session" value="{{ $session }}"><input type="hidden" name="term" value="{{ $term }}">
@if(in_array($role,['form_teacher','school_admin']) && $st==='submitted' || in_array($role,['principal','school_admin']) && $st==='reviewed')
<h3>{{ $st==='submitted' ? 'Form teacher comments' : 'Principal comments' }}</h3>
<div class="tablewrap"><table>@foreach($students as $s)<tr><td style="width:30%">{{ $s->full_name }}</td><td><input name="comments[{{ $s->id }}]" maxlength="500" value="{{ $role==='principal' || $st==='reviewed' ? ($comments[$s->id]->principal_comment ?? '') : ($comments[$s->id]->teacher_comment ?? '') }}" placeholder="Optional comment for the report card"></td></tr>@endforeach</table></div>
<div class="field mt"><label>Overall class note</label><textarea name="comment" rows="2">{{ $st==='submitted' ? $review->form_teacher_comment ?? '' : $review->principal_comment ?? '' }}</textarea></div>@endif
<div class="row" style="justify-content:flex-start">
@if($st==='submitted' && in_array($role,['form_teacher','school_admin']))<button class="btn primary" name="do" value="mark_reviewed">Mark reviewed → principal</button>@endif
@if($st==='reviewed' && in_array($role,['principal','school_admin']))<button class="btn primary" name="do" value="approve">Approve class</button>@endif
@if(in_array($st,['reviewed','approved']) && in_array($role,['principal','school_admin']))<button class="btn" name="do" value="return_to_form">Return to form teacher</button>@endif
@if($st==='approved' && $role==='school_admin')<button class="btn primary" name="do" value="publish" onclick="return confirm('Publish now? Students and parents will see these results.')">Publish results</button>@endif
</div></form>
@elseif($st==='waiting')<div class="flash err">Waiting on subject teachers to submit every sheet.</div>
@elseif($st==='published')<div class="flash ok">Published {{ $review->published_at?->format('j M Y H:i') }}.</div>@endif
@endsection
