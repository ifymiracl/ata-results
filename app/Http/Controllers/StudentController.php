<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\School;
use App\Models\Student;
use App\Services\Audit;
use App\Services\Csv;
use App\Services\Portal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class StudentController extends Controller
{
    public function index(Request $r)
    {
        $school = $this->school();
        $q = Student::where('school_id', $school->id)->with('department');
        if ($r->filled('class')) { $q->where('class_name', $r->class); }
        if ($r->filled('q')) { $s = $r->q; $q->where(fn ($w) => $w->where('first_name', 'like', "%$s%")->orWhere('last_name', 'like', "%$s%")->orWhere('admission_no', 'like', "%$s%")); }
        return view('school.students', [
            'students' => $q->orderBy('class_name')->orderBy('first_name')->paginate(40)->withQueryString(),
            'classes' => $school->classes, 'departments' => Department::where('school_id', $school->id)->get(),
        ]);
    }

    private function rules(): array
    {
        return ['first_name' => 'required|max:100', 'middle_name' => 'nullable|max:100', 'last_name' => 'required|max:100', 'gender' => 'nullable|in:M,F',
            'class_name' => 'required|max:100', 'department_id' => 'nullable|exists:departments,id', 'guardian_name' => 'nullable|max:190',
            'guardian_relationship' => 'nullable|max:30', 'guardian_phone' => 'nullable|max:60', 'guardian_email' => 'nullable|email',
            'address' => 'nullable|max:255', 'date_of_birth' => 'nullable|date', 'photo' => 'nullable|image|max:1024'];
    }

    public static function nextAdmissionNo(School $school): string
    {
        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $school->short_name ?: $school->name), 0, 3)) ?: 'STU';
        $n = Student::where('school_id', $school->id)->count() + 1;
        do { $no = $prefix . '/' . date('y') . '/' . str_pad((string) $n++, 4, '0', STR_PAD_LEFT); } while (Student::where('school_id', $school->id)->where('admission_no', $no)->exists());
        return $no;
    }

    private function mailParent(School $school, Student $s, string $pin, string $parentPin): void
    {
        if (! $s->guardian_email) { return; }
        Mail::raw("{$s->full_name} is registered at {$school->name}.\n\nSign in: " . route('login', $school) . "\nParent login: {$s->guardian_email} (or phone)\nParent PIN: $parentPin\nStudent ID: {$s->admission_no}\nStudent PIN: $pin\n\nYou will choose a new PIN on first sign-in.", fn ($m) => $m->to($s->guardian_email)->subject('Portal access for ' . $s->first_name));
    }

    public function store(Request $r, School $school)
    {
        $d = $r->validate($this->rules() + ['admission_no' => 'nullable|max:60']);
        $admission = ($d['admission_no'] ?? null) ?: self::nextAdmissionNo($school);
        if (Student::where('school_id', $school->id)->where('admission_no', $admission)->exists()) { return back()->withErrors(['admission_no' => 'That admission number already exists.'])->withInput(); }
        [$pin, $parentPin] = [Portal::generatePin(), Portal::generatePin()];
        $s = Student::create(collect($d)->except('photo', 'admission_no')->all() + [
            'school_id' => $school->id, 'admission_no' => $admission, 'pin_hash' => Hash::make($pin), 'parent_pin_hash' => Hash::make($parentPin),
            'photo_path' => $r->hasFile('photo') ? $r->file('photo')->store('students', 'public') : null,
        ]);
        $this->mailParent($school, $s, $pin, $parentPin);
        Audit::by($this->me(), 'student.created', $s->admission_no);
        return back()->with('ok', "Student added. ID {$s->admission_no} · student PIN $pin · parent PIN $parentPin (shown once).");
    }

    public function update(Request $r, School $school, $id)
    {
        $s = Student::where('school_id', $school->id)->findOrFail($id);
        $d = $r->validate($this->rules() + ['status' => 'required|in:active,inactive,graduated']);
        $fill = collect($d)->except('photo')->all();
        if ($r->hasFile('photo')) { $fill['photo_path'] = $r->file('photo')->store('students', 'public'); }
        $s->update($fill);
        return back()->with('ok', 'Student updated.');
    }

    public function resetPin(Request $r, School $school, $id)
    {
        $s = Student::where('school_id', $school->id)->findOrFail($id);
        $pin = Portal::generatePin();
        if ($r->input('who') === 'parent') { $s->update(['parent_pin_hash' => Hash::make($pin), 'parent_must_change_pin' => true]); $who = 'parent'; }
        else { $s->update(['pin_hash' => Hash::make($pin), 'must_change_pin' => true]); $who = 'student'; }
        Audit::by($this->me(), 'student.pin_reset', $s->admission_no . ' ' . $who);
        return back()->with('ok', "New $who PIN for {$s->full_name}: $pin (shown once).");
    }

    public function import(Request $r, School $school)
    {
        $r->validate(['file' => 'required|file|mimes:csv,txt|max:4096']);
        $classes = $school->classes->pluck('name')->all(); $deps = Department::where('school_id', $school->id)->pluck('id', 'name');
        $made = []; $skipped = [];
        foreach (Csv::read($r->file('file')) as $i => $row) {
            $first = $row['first_name'] ?? ''; $last = $row['last_name'] ?? ''; $class = $row['class'] ?? ($row['class_name'] ?? '');
            if ($first === '' || $last === '' || ! in_array($class, $classes, true)) { $skipped[] = 'row ' . ($i + 2) . ': needs first_name, last_name and an existing class'; continue; }
            $no = ($row['admission_no'] ?? '') ?: self::nextAdmissionNo($school);
            if (Student::where('school_id', $school->id)->where('admission_no', $no)->exists()) { $skipped[] = "row " . ($i + 2) . ": $no already exists"; continue; }
            [$pin, $pp] = [Portal::generatePin(), Portal::generatePin()];
            $s = Student::create([
                'school_id' => $school->id, 'admission_no' => $no, 'first_name' => $first, 'middle_name' => $row['middle_name'] ?? null, 'last_name' => $last,
                'gender' => in_array($row['gender'] ?? '', ['M', 'F']) ? $row['gender'] : null, 'class_name' => $class, 'department_id' => $deps[$row['department'] ?? ''] ?? null,
                'guardian_name' => $row['guardian_name'] ?? null, 'guardian_phone' => $row['guardian_phone'] ?? null, 'guardian_email' => $row['guardian_email'] ?? null,
                'date_of_birth' => ($row['date_of_birth'] ?? '') ?: null, 'pin_hash' => Hash::make($pin), 'parent_pin_hash' => Hash::make($pp),
            ]);
            $this->mailParent($school, $s, $pin, $pp);
            $made[] = [$no, $s->full_name, $pin, $pp];
        }
        Audit::by($this->me(), 'students.imported', count($made) . ' rows');
        return back()->with('ok', count($made) . ' students imported' . ($skipped ? ', ' . count($skipped) . ' skipped: ' . implode('; ', array_slice($skipped, 0, 5)) : '.'))->with('credentials', $made);
    }

    public function export(School $school)
    {
        $rows = Student::where('school_id', $school->id)->orderBy('class_name')->orderBy('first_name')->get()->map(fn ($s) => [$s->admission_no, $s->first_name, $s->middle_name, $s->last_name, $s->gender, $s->class_name, $s->guardian_name, $s->guardian_phone, $s->guardian_email, $s->status]);
        return Csv::download($school->slug . '-students.csv', ['admission_no', 'first_name', 'middle_name', 'last_name', 'gender', 'class', 'guardian_name', 'guardian_phone', 'guardian_email', 'status'], $rows);
    }

    public function classList(School $school, $class)
    {
        abort_unless(in_array($class, $this->classesFor($this->staff()), true), 403);
        return view('school.class-list', ['class' => $class, 'students' => Student::where('school_id', $school->id)->where('class_name', $class)->active()->orderBy('first_name')->get()]);
    }
}
