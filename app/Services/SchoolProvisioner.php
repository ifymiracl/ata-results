<?php

namespace App\Services;

use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Staff;
use App\Models\Subject;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SchoolProvisioner
{
    public const LEVELS = [
        'nursery' => ['Nursery 1', 'Nursery 2'],
        'primary' => ['Primary 1', 'Primary 2', 'Primary 3', 'Primary 4', 'Primary 5', 'Primary 6'],
        'jss' => ['JSS 1', 'JSS 2', 'JSS 3'],
        'ss' => ['SS 1', 'SS 2', 'SS 3'],
    ];

    public static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'school';
        $slug = $base; $i = 2;
        while (School::where('slug', $slug)->exists() || preg_match('/^(platform|find-school|register-school|verify|webhooks|up|storage|build)$/', $slug)) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }

    /** @return array{school:School,admin:Staff,pin:string} */
    public static function create(array $d, array $levels = ['primary', 'jss', 'ss']): array
    {
        $year = (int) date('Y');
        $school = School::create([
            'name' => $d['name'], 'short_name' => $d['short_name'] ?? $d['name'],
            'slug' => self::uniqueSlug($d['name']), 'code' => 'ATA-' . strtoupper(Str::random(6)),
            'state' => $d['state'] ?? null, 'country' => $d['country'] ?? null,
            'phone' => $d['phone'] ?? null, 'email' => $d['email'] ?? null,
            'current_session' => $year . '/' . ($year + 1), 'current_term' => 'First Term',
            'trial_ends_at' => now()->addDays((int) \App\Models\Setting::get('trial_days', 30)),
            'plan_name' => 'Trial',
        ]);

        $order = 0;
        foreach ($levels as $lv) {
            foreach (self::LEVELS[$lv] ?? [] as $c) { SchoolClass::create(['school_id' => $school->id, 'name' => $c, 'sort_order' => $order++]); }
        }
        foreach (['English Language' => 1, 'Mathematics' => 1, 'Basic Science' => 1, 'Civic Education' => 1, 'Computer Studies' => 1, 'Physics' => 0, 'Chemistry' => 0, 'Biology' => 0, 'Economics' => 0, 'Literature' => 0] as $n => $core) {
            Subject::create(['school_id' => $school->id, 'name' => $n, 'is_core' => (bool) $core]);
        }

        $pin = Portal::generatePin();
        $admin = Staff::create([
            'school_id' => $school->id, 'staff_code' => 'ADM-0001', 'name' => $d['admin_name'] ?? 'School Admin',
            'phone' => $d['phone'] ?? null, 'email' => $d['email'] ?? null, 'role' => 'school_admin',
            'position_title' => 'Administrator', 'pin_hash' => Hash::make($pin), 'must_change_pin' => true,
        ]);
        return compact('school', 'admin', 'pin');
    }

    public static function nextStaffCode(School $school): string
    {
        $n = Staff::where('school_id', $school->id)->count() + 1;
        do { $code = 'STF-' . str_pad((string) $n++, 4, '0', STR_PAD_LEFT); } while (Staff::where('school_id', $school->id)->where('staff_code', $code)->exists());
        return $code;
    }
}
