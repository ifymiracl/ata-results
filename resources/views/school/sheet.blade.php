@extends('layouts.app')
@section('title',"$subject · $class")
@section('content')
<p><a href="{{ route('scores', $school) }}">← All sheets</a></p>
<h1>{{ $subject }} <small class="muted">{{ $class }} · {{ $session }} · {{ $term }}</small></h1>
@if($locked)<div class="flash ok">This sheet is submitted and locked. The form teacher can send it back for edits{{ $me['model']->role==='school_admin' ? ' — you can still edit as admin' : '' }}.</div>@endif
@php $canEdit = ! $locked || $me['model']->role === 'school_admin'; @endphp
<form method="post" action="{{ route('scores.save', $school) }}" class="card">@csrf
@foreach(['class'=>$class,'subject'=>$subject,'session'=>$session,'term'=>$term] as $k=>$v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
<div class="tablewrap"><table id="sheet"><tr><th>#</th><th>Student</th><th>CA 1 (/{{ $weights['ca1'] }})</th><th>CA 2 (/{{ $weights['ca2'] }})</th><th>Exam (/{{ $weights['exam'] }})</th><th>Total</th><th>Grade</th></tr>
@forelse($students as $i => $s) @php $x = $results[$s->id] ?? null; @endphp
<tr><td>{{ $i+1 }}</td><td>{{ $s->full_name }}<br><span class="muted">{{ $s->admission_no }}</span></td>
@foreach(['ca1','ca2','exam'] as $f)<td><input type="number" step="0.5" min="0" max="{{ $weights[$f] }}" name="score[{{ $s->id }}][{{ $f }}]" value="{{ $x?->$f }}" data-f="{{ $f }}" @disabled(! $canEdit)></td>@endforeach
<td class="tot">{{ $x?->total }}</td><td>{{ $x?->grade }}</td></tr>
@empty<tr><td colspan="7" class="muted">No eligible students in this class for this subject (check departments for electives).</td></tr>@endforelse</table></div>
@if($canEdit && $students->count())<p class="mt"><button class="btn primary">Save draft</button></p>@endif</form>
@if($canEdit && $students->count())
<div class="grid g2"><form method="post" action="{{ route('scores.submit', $school) }}" class="card" onsubmit="return confirm('Submit this sheet? It will lock until the form teacher returns it.')">@csrf
@foreach(['class'=>$class,'subject'=>$subject,'session'=>$session,'term'=>$term] as $k=>$v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
<h3>Submit for review</h3><p class="muted">Save first. Every student must have a score.</p><button class="btn">Submit sheet</button></form>
<form method="post" action="{{ route('scores.import', $school) }}" enctype="multipart/form-data" class="card">@csrf
@foreach(['class'=>$class,'subject'=>$subject,'session'=>$session,'term'=>$term] as $k=>$v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
<h3>Bulk entry via CSV</h3><p><a href="{{ route('scores.template', ['school'=>$school,'class'=>$class,'subject'=>$subject]) }}">Download template</a></p><div class="field"><input type="file" name="file" accept=".csv,.txt" required></div><button class="btn">Import as draft</button></form></div>
@endif
<script>document.querySelectorAll('#sheet tr').forEach(r=>{const i=r.querySelectorAll('input');if(!i.length)return;const t=r.querySelector('.tot');const up=()=>{t.textContent=[...i].reduce((a,x)=>a+(parseFloat(x.value)||0),0)};i.forEach(x=>x.addEventListener('input',up))})</script>
@endsection
