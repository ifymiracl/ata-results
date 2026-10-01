{{-- expects $student, $card, $school, $scale --}}
<div class="paper">
<div class="rc-head"><div><h2 style="margin:0">{{ $school->name }}</h2><div>{{ $school->address }} {{ $school->state }}</div><div style="font-style:italic">{{ $school->motto }}</div></div>
<div style="text-align:center"><div class="qr">{!! $card['qr'] !!}</div><small>Verify: {{ $card['code'] }}</small></div></div>
<h3 style="text-align:center">Report Card — {{ $card['term'] }}, {{ $card['session'] }}</h3>
<p><b>{{ $student->full_name }}</b> · {{ $student->admission_no }} · {{ $student->class_name }} · Position <b>{{ $card['position'] ? \App\Services\Results::ordinal($card['position']) : '—' }}</b> of {{ $card['population'] }}</p>
<table><tr><th>Subject</th><th>CA 1</th><th>CA 2</th><th>Exam</th><th>Total</th><th>Grade</th><th>Class avg</th><th>Highest</th><th>Remark</th></tr>
@foreach($card['rows'] as $r)@php $st = $card['stats'][$r->subject] ?? null; @endphp<tr><td>{{ $r->subject }}</td><td>{{ $r->ca1 + 0 }}</td><td>{{ $r->ca2 + 0 }}</td><td>{{ $r->exam + 0 }}</td><td><b>{{ $r->total + 0 }}</b></td><td>{{ $r->grade }}</td><td>{{ $st ? round($st->avg,1) : '' }}</td><td>{{ $st ? $st->high + 0 : '' }}</td><td>{{ $r->remark }}</td></tr>@endforeach
<tr><th colspan="4" style="text-align:right">Total / Average</th><th>{{ $card['total'] + 0 }}</th><th colspan="4">{{ $card['average'] }}%</th></tr></table>
@if($card['skills']->count())<h3 style="margin-top:14px">Conduct &amp; skills</h3><div class="skills">@foreach($card['skills'] as $sk => $v)<div>{{ $sk }}: <b>{{ $v }}</b>/5</div>@endforeach</div>@endif
<p style="margin-top:12px">Attendance: <b>{{ $card['attendance']['present'] }}</b> of {{ $card['attendance']['days'] }} days @if($card['attendance']['days'])({{ round($card['attendance']['present'] / $card['attendance']['days'] * 100) }}%)@endif</p>
@if($card['annual'])<p><b>Annual:</b> average {{ $card['annual']->annual_average }}% · class position {{ $card['annual']->class_position }}/{{ $card['annual']->class_population }} · <b>{{ ucfirst($card['annual']->recommended_action) }}</b>{{ $card['annual']->recommended_class ? ' → ' . $card['annual']->recommended_class : '' }}</p>@endif
@foreach($card['awards'] as $a)<p>🏆 {{ $a->award_type === 'overall_best' ? 'Overall best student, ' . $a->level_label : 'Best in ' . $a->subject . ', ' . $a->level_label }}</p>@endforeach
<p><b>Form teacher:</b> {{ $card['comments']->teacher_comment ?? $card['review']->form_teacher_comment ?? '—' }}<br><b>Principal:</b> {{ $card['comments']->principal_comment ?? $card['review']->principal_comment ?? '—' }}</p>
@if($school->next_term_begins)<p>Next term begins: <b>{{ $school->next_term_begins->format('j F Y') }}</b></p>@endif
<small>Grading: @foreach($scale as $b){{ $b['grade'] }} ≥{{ $b['min'] + 0 }} @endforeach</small>
</div>
