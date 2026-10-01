<?php

namespace App\Http\Controllers;

use App\Models\ClassSubject;
use App\Models\Department;
use App\Models\DepartmentSubject;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SlugHistory;
use App\Models\Subject;
use App\Services\Audit;
use App\Services\SchoolProvisioner;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    public function index()
    {
        $school = $this->school();
        return view('school.admin', [
            'classes' => $school->classes, 'subjects' => $school->subjects,
            'departments' => Department::where('school_id', $school->id)->with('subjects')->orderBy('name')->get(),
            'plan' => ClassSubject::where('school_id', $school->id)->get()->groupBy('class_name')->map->pluck('subject'),
            'scale' => $school->scale(), 'weights' => $school->weights(),
        ]);
    }

    public function saveSettings(Request $r, School $school)
    {
        $d = $r->validate([
            'name' => 'required|max:190', 'short_name' => 'nullable|max:100', 'slug' => 'required|alpha_dash|max:100',
            'address' => 'nullable|max:255', 'state' => 'nullable|max:100', 'country' => 'nullable|max:100', 'phone' => 'nullable|max:60',
            'email' => 'nullable|email', 'motto' => 'nullable|max:190', 'current_session' => 'required|regex:/^\d{4}\/\d{4}$/',
            'current_term' => 'required|in:First Term,Second Term,Third Term', 'promotion_benchmark' => 'required|numeric|between:0,100',
            'next_term_begins' => 'nullable|date', 'w_ca1' => 'required|numeric|min:0|max:100', 'w_ca2' => 'required|numeric|min:0|max:100', 'w_exam' => 'required|numeric|min:0|max:100',
            'paystack_public_key' => 'nullable|max:190', 'paystack_secret_key' => 'nullable|max:190', 'logo' => 'nullable|image|max:1024',
        ]);
        if ($d['w_ca1'] + $d['w_ca2'] + $d['w_exam'] != 100) { return back()->withErrors(['w_exam' => 'Score weights (CA1 + CA2 + Exam) must add up to 100.'])->withInput(); }

        $slug = Str::slug($d['slug']);
        if ($slug !== $school->slug) {
            if (School::where('slug', $slug)->where('id', '!=', $school->id)->exists() || SchoolProvisioner::uniqueSlug($slug) !== $slug && ! School::where('slug', $slug)->exists()) {
                return back()->withErrors(['slug' => 'That web address is taken or reserved.'])->withInput();
            }
            SlugHistory::create(['school_id' => $school->id, 'old_slug' => $school->slug]);
        }
        $fill = collect($d)->only(['name', 'short_name', 'address', 'state', 'country', 'phone', 'email', 'motto', 'current_session', 'current_term', 'promotion_benchmark', 'next_term_begins', 'paystack_public_key'])->all();
        $fill['slug'] = $slug;
        $fill['score_weights'] = ['ca1' => (float) $d['w_ca1'], 'ca2' => (float) $d['w_ca2'], 'exam' => (float) $d['w_exam']];
        if ($r->filled('paystack_secret_key')) { $fill['paystack_secret_key'] = $d['paystack_secret_key']; }
        if ($r->hasFile('logo')) { $fill['logo_path'] = $r->file('logo')->store('logos', 'public'); }

        // grading scale: rows of min/grade/remark
        $scale = [];
        foreach ((array) $r->input('scale', []) as $row) {
            if (($row['grade'] ?? '') !== '' && is_numeric($row['min'] ?? null)) { $scale[] = ['min' => (float) $row['min'], 'grade' => $row['grade'], 'remark' => $row['remark'] ?? '']; }
        }
        if ($scale) { $fill['grading_scale'] = $scale; }

        $school->update($fill);
        Audit::by($this->me(), 'settings.saved');
        return redirect()->route('admin.index', $school)->with('ok', 'Settings saved.');
    }

    public function saveClass(Request $r, School $school)
    {
        $d = $r->validate(['name' => 'required|max:100', 'capacity' => 'nullable|integer|min:1']);
        SchoolClass::updateOrCreate(['school_id' => $school->id, 'name' => $d['name']], ['capacity' => $d['capacity'] ?? null, 'sort_order' => SchoolClass::where('school_id', $school->id)->count()]);
        return back()->with('ok', 'Class saved.');
    }

    public function deleteClass(School $school, $id)
    {
        $c = SchoolClass::where('school_id', $school->id)->findOrFail($id);
        if (\App\Models\Student::where('school_id', $school->id)->where('class_name', $c->name)->exists()) { return back()->with('err', 'That class still has students.'); }
        $c->delete();
        return back()->with('ok', 'Class removed.');
    }

    public function saveSubject(Request $r, School $school)
    {
        $d = $r->validate(['name' => 'required|max:100']);
        Subject::updateOrCreate(['school_id' => $school->id, 'name' => $d['name']], ['is_core' => $r->boolean('is_core')]);
        return back()->with('ok', 'Subject saved.');
    }

    public function deleteSubject(School $school, $id)
    {
        Subject::where('school_id', $school->id)->whereKey($id)->delete();
        return back()->with('ok', 'Subject removed.');
    }

    /** Which subjects a class takes (the plan used for report cards). */
    public function saveClassSubjects(Request $r, School $school)
    {
        $d = $r->validate(['class_name' => 'required|max:100', 'subjects' => 'nullable|array']);
        ClassSubject::where('school_id', $school->id)->where('class_name', $d['class_name'])->delete();
        foreach (array_unique($d['subjects'] ?? []) as $s) { ClassSubject::create(['school_id' => $school->id, 'class_name' => $d['class_name'], 'subject' => $s]); }
        return back()->with('ok', 'Subject plan saved for ' . $d['class_name'] . '.');
    }

    public function saveDepartment(Request $r, School $school)
    {
        $d = $r->validate(['name' => 'required|max:100', 'subjects' => 'nullable|array']);
        $dep = Department::updateOrCreate(['school_id' => $school->id, 'name' => $d['name']]);
        DepartmentSubject::where('department_id', $dep->id)->delete();
        foreach (array_unique($d['subjects'] ?? []) as $s) { DepartmentSubject::create(['department_id' => $dep->id, 'subject' => $s]); }
        return back()->with('ok', 'Department saved.');
    }

    public function deleteDepartment(School $school, $id)
    {
        Department::where('school_id', $school->id)->whereKey($id)->delete();
        return back()->with('ok', 'Department removed.');
    }
}
