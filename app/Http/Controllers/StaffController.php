<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\Staff;
use App\Models\StaffAssignment;
use App\Services\Audit;
use App\Services\Csv;
use App\Services\Portal;
use App\Services\SchoolProvisioner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class StaffController extends Controller
{
    public function index()
    {
        $school = $this->school();
        return view('school.staff', [
            'staff' => Staff::where('school_id', $school->id)->with('assignments')->orderBy('name')->get(),
            'classes' => $school->classes, 'subjects' => $school->subjects,
        ]);
    }

    private function rules(): array
    {
        return ['name' => 'required|max:190', 'phone' => 'nullable|max:60', 'email' => 'nullable|email', 'gender' => 'nullable|in:M,F',
            'position_title' => 'nullable|max:100', 'role' => 'required|in:' . implode(',', array_keys(Staff::ROLES)), 'assigned_class' => 'nullable|max:100'];
    }

    private function notify(School $school, Staff $s, string $pin): void
    {
        if (! $s->email) { return; }
        Mail::raw("Welcome to {$school->name} on ATA Results.\n\nSign in: " . route('login', $school) . "\nUser ID: {$s->staff_code}\nTemporary PIN: $pin\n\nYou will choose a new PIN on first sign-in.", fn ($m) => $m->to($s->email)->subject('Your ATA Results login'));
    }

    public function store(Request $r, School $school)
    {
        $d = $r->validate($this->rules());
        $pin = Portal::generatePin();
        $s = Staff::create($d + [
            'school_id' => $school->id, 'staff_code' => SchoolProvisioner::nextStaffCode($school),
            'pin_hash' => Hash::make($pin), 'must_change_pin' => true,
            'assigned_class' => $d['role'] === 'form_teacher' ? ($d['assigned_class'] ?? null) : null,
        ]);
        $this->notify($school, $s, $pin);
        Audit::by($this->me(), 'staff.created', $s->staff_code);
        return back()->with('ok', "Staff added. ID {$s->staff_code}, temporary PIN $pin (shown once).");
    }

    public function update(Request $r, School $school, $id)
    {
        $s = Staff::where('school_id', $school->id)->findOrFail($id);
        $d = $r->validate($this->rules() + ['status' => 'required|in:active,inactive']);
        if ($s->id === $this->staff()->id && ($d['status'] !== 'active' || $d['role'] !== 'school_admin')) {
            return back()->with('err', 'You cannot demote or deactivate your own account.');
        }
        $s->update($d + ['assigned_class' => $d['role'] === 'form_teacher' ? ($d['assigned_class'] ?? null) : null]);
        return back()->with('ok', 'Staff updated.');
    }

    public function resetPin(School $school, $id)
    {
        $s = Staff::where('school_id', $school->id)->findOrFail($id);
        $pin = Portal::generatePin();
        $s->update(['pin_hash' => Hash::make($pin), 'must_change_pin' => true]);
        $this->notify($school, $s, $pin);
        Audit::by($this->me(), 'staff.pin_reset', $s->staff_code);
        return back()->with('ok', "New temporary PIN for {$s->name}: $pin (shown once).");
    }

    public function assign(Request $r, School $school, $id)
    {
        $s = Staff::where('school_id', $school->id)->findOrFail($id);
        $d = $r->validate(['class_name' => 'required|max:100', 'subject' => 'required|max:100']);
        StaffAssignment::firstOrCreate(['staff_id' => $s->id, 'class_name' => $d['class_name'], 'subject' => $d['subject']], ['school_id' => $school->id]);
        return back()->with('ok', 'Assignment added.');
    }

    public function unassign(School $school, $id)
    {
        StaffAssignment::where('school_id', $school->id)->whereKey($id)->delete();
        return back()->with('ok', 'Assignment removed.');
    }

    public function import(Request $r, School $school)
    {
        $r->validate(['file' => 'required|file|mimes:csv,txt|max:2048']);
        $made = []; $skipped = 0;
        foreach (Csv::read($r->file('file')) as $row) {
            if (($row['name'] ?? '') === '') { $skipped++; continue; }
            $role = array_key_exists($row['role'] ?? '', Staff::ROLES) ? $row['role'] : 'subject_teacher';
            $pin = Portal::generatePin();
            $s = Staff::create([
                'school_id' => $school->id, 'staff_code' => SchoolProvisioner::nextStaffCode($school), 'name' => $row['name'],
                'phone' => $row['phone'] ?? null, 'email' => $row['email'] ?? null, 'role' => $role,
                'assigned_class' => $role === 'form_teacher' ? ($row['form_class'] ?? null) : null,
                'position_title' => $row['position_title'] ?? null, 'pin_hash' => Hash::make($pin), 'must_change_pin' => true,
            ]);
            $this->notify($school, $s, $pin);
            $made[] = [$s->staff_code, $s->name, $pin];
        }
        Audit::by($this->me(), 'staff.imported', count($made) . ' rows');
        return back()->with('ok', count($made) . ' staff imported, ' . $skipped . ' skipped.')->with('credentials', $made);
    }
}
