<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\AuditLog;
use App\Services\Audit;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function index()
    {
        $me = $this->me(); $school = $this->school();
        $aud = $me['type'] === 'staff' ? ['all', 'staff'] : ($me['type'] === 'student' ? ['all', 'students'] : ['all', 'parents']);
        $class = $me['type'] === 'staff' ? null : $me['model']->class_name;
        $q = Announcement::where('school_id', $school->id);
        if (! $this->isAdmin()) {
            $q->whereIn('audience', $aud)->where(fn ($w) => $w->whereNull('class_name')->when($class, fn ($x) => $x->orWhere('class_name', $class)));
        }
        return view('school.announcements', ['items' => $q->orderByDesc('pinned')->latest()->get(), 'classes' => $school->classes]);
    }

    public function store(Request $r)
    {
        $d = $r->validate(['title' => 'required|max:190', 'body' => 'required|max:5000', 'audience' => 'required|in:all,staff,students,parents', 'class_name' => 'nullable|max:100', 'pinned' => 'nullable|boolean']);
        $a = Announcement::create($d + ['school_id' => $this->school()->id, 'author' => $this->staff()->name, 'pinned' => (bool) $r->boolean('pinned'), 'class_name' => ($d['class_name'] ?? null) ?: null]);
        Audit::by($this->me(), 'announcement.posted', $a->title);
        return back()->with('ok', 'Notice posted.');
    }

    public function destroy($school, $id)
    {
        Announcement::where('school_id', $this->school()->id)->whereKey($id)->delete();
        return back()->with('ok', 'Notice removed.');
    }

    public function audit()
    {
        return view('school.audit', ['logs' => AuditLog::where('school_id', $this->school()->id)->latest()->limit(300)->get()]);
    }
}
