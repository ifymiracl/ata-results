@if(session('credentials'))
<div class="card"><h3>Login details (shown once — print or copy now)</h3>
<div class="tablewrap"><table><tbody>@foreach(session('credentials') as $c)<tr>@foreach($c as $i => $v)<td>@if($i >= 2)<span class="pin">{{ $v }}</span>@else{{ $v }}@endif</td>@endforeach</tr>@endforeach</tbody></table></div>
<p class="muted">Columns: ID, name, then PIN(s) — student PIN then parent PIN for students.</p></div>
@endif
