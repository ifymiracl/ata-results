<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\DepartmentSubject;
use App\Models\Setting;
use App\Models\Staff;
use App\Models\StaffAssignment;
use App\Models\Student;
use App\Models\User;
use App\Services\SchoolProvisioner;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /** Demo data. Every demo PIN is 123456. Platform admin: admin@ata.test / password. */
    public function run(): void
    {
        User::updateOrCreate(['email' => 'admin@ata.test'], ['name' => 'Platform Admin', 'password' => Hash::make('password'), 'is_platform_admin' => true]);
        Setting::put('price_per_student', 100);

        $made = SchoolProvisioner::create(['name' => 'Greenfield Academy', 'state' => 'Lagos', 'country' => 'Nigeria', 'email' => 'office@greenfield.test', 'admin_name' => 'Ada Admin']);
        $school = $made['school'];
        $school->update(['slug' => 'greenfield', 'next_term_begins' => now()->addMonths(2)]);
        $pin = Hash::make('123456');
        $made['admin']->update(['pin_hash' => $pin, 'must_change_pin' => false]);

        $mk = fn ($code, $name, $role, $extra = []) => Staff::create(['school_id' => $school->id, 'staff_code' => $code, 'name' => $name, 'role' => $role, 'email' => strtolower($code) . '@greenfield.test', 'pin_hash' => $pin, 'must_change_pin' => false] + $extra);
        $principal = $mk('PRN-0001', 'Peter Principal', 'principal');
        $form = $mk('FRM-0001', 'Funke Form', 'form_teacher', ['assigned_class' => 'JSS 1']);
        $math = $mk('TCH-0001', 'Tunde Mathematics', 'subject_teacher');
        $eng = $mk('TCH-0002', 'Eze English', 'subject_teacher');
        foreach ([[$math, 'Mathematics'], [$eng, 'English Language']] as [$t, $sub]) {
            StaffAssignment::create(['school_id' => $school->id, 'staff_id' => $t->id, 'class_name' => 'JSS 1', 'subject' => $sub]);
        }

        $science = Department::create(['school_id' => $school->id, 'name' => 'Science']);
        DepartmentSubject::create(['department_id' => $science->id, 'subject' => 'Physics']);

        $names = [['Chidi', 'Okafor', 'M'], ['Amina', 'Bello', 'F'], ['Tola', 'Adeyemi', 'F'], ['Segun', 'Balogun', 'M'], ['Ngozi', 'Eze', 'F'], ['Ibrahim', 'Musa', 'M']];
        foreach ($names as $i => [$f, $l, $g]) {
            Student::create([
                'school_id' => $school->id, 'admission_no' => 'GRE/26/' . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT), 'first_name' => $f, 'last_name' => $l, 'gender' => $g,
                'class_name' => 'JSS 1', 'department_id' => $i % 2 ? $science->id : null, 'guardian_name' => "Mr/Mrs $l", 'guardian_phone' => '08030000' . str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'guardian_email' => strtolower($l) . '@parent.test', 'pin_hash' => $pin, 'parent_pin_hash' => $pin, 'must_change_pin' => false, 'parent_must_change_pin' => false,
            ]);
        }
        $this->command?->info('Demo ready: /greenfield — ADM-0001, PRN-0001, FRM-0001, TCH-0001, TCH-0002 (PIN 123456); students GRE/26/0001…; parents by guardian phone 08030000001…');
    }
}
