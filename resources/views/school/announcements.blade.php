@extends('layouts.app')
@section('title','Notices')
@section('content')
<h1>Notices</h1>
@if($me['type']==='staff' && $me['model']->role==='school_admin')
<form method="post" action="{{ route('admin.announcements.store', $school) }}" class="card">@csrf<h3>Post a notice</h3>
<div class="grid g2"><div class="field"><label>Title</label><input name="title" required></div><div class="field"><label>Audience</label><select name="audience"><option value="all">Everyone</option><option value="staff">Staff</option><option value="students">Students</option><option value="parents">Parents</option></select></div>
<div class="field"><label>Only for class (optional)</label><select name="class_name"><option value="">All classes</option>@foreach($classes as $c)<option>{{ $c->name }}</option>@endforeach</select></div><div class="field"><label><input type="checkbox" name="pinned" value="1"> Pin to top</label></div></div>
<div class="field"><label>Message</label><textarea name="body" rows="3" required></textarea></div><button class="btn primary">Post</button></form>@endif
@forelse($items as $n)<div class="card"><div class="row"><b>{{ $n->pinned ? '📌 ' : '' }}{{ $n->title }}</b>@if($me['type']==='staff' && $me['model']->role==='school_admin')<form method="post" action="{{ route('admin.announcements.delete', [$school, $n->id]) }}" style="flex:0">@csrf<button class="btn sm danger">Delete</button></form>@endif</div>
<p class="muted">{{ $n->created_at->format('j M Y') }} · {{ $n->author }} · {{ $n->audience }}{{ $n->class_name ? ' · ' . $n->class_name : '' }}</p><p>{!! nl2br(e($n->body)) !!}</p></div>@empty<p class="muted">No notices yet.</p>@endforelse
@endsection
