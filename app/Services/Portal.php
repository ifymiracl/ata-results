<?php

namespace App\Services;

use App\Models\School;
use App\Models\Staff;
use App\Models\Student;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

/**
 * PIN-based login for staff, students and parents, kept in the Laravel
 * session (separate from the platform-admin `users` guard).
 */
class Portal
{
    public const MAX_ATTEMPTS = 8;
    public const DECAY = 900;

    public static function generatePin(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    public static function findSubject(School $school, string $identifier): ?array
    {
        $identifier = trim($identifier);
        if ($identifier === '') { return null; }

        $staff = Staff::where('school_id', $school->id)->where('status', 'active')
            ->where(fn ($q) => $q->where('staff_code', $identifier)->orWhere('phone', $identifier)->orWhere('email', $identifier))->first();
        if ($staff) { return ['type' => 'staff', 'model' => $staff, 'hash' => $staff->pin_hash]; }

        $student = Student::where('school_id', $school->id)->where('status', 'active')->where('admission_no', $identifier)->first();
        if ($student) { return ['type' => 'student', 'model' => $student, 'hash' => $student->pin_hash]; }

        $parent = Student::where('school_id', $school->id)->where('status', 'active')
            ->where(fn ($q) => $q->where('guardian_phone', $identifier)->orWhere('guardian_email', $identifier))->first();
        if ($parent) { return ['type' => 'parent', 'model' => $parent, 'hash' => $parent->parent_pin_hash]; }

        return null;
    }

    public static function throttleKey(School $school, string $identifier, string $ip): string
    {
        return 'portal|' . $school->id . '|' . strtolower($identifier) . '|' . $ip;
    }

    public static function attempt(School $school, string $identifier, string $pin, string $ip): ?array
    {
        $key = self::throttleKey($school, $identifier, $ip);
        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) { return null; }
        $subject = self::findSubject($school, $identifier);
        if (! $subject || ! $subject['hash'] || ! Hash::check($pin, $subject['hash'])) {
            RateLimiter::hit($key, self::DECAY);
            return null;
        }
        RateLimiter::clear($key);
        return $subject;
    }

    public static function throttled(School $school, string $identifier, string $ip): bool
    {
        return RateLimiter::tooManyAttempts(self::throttleKey($school, $identifier, $ip), self::MAX_ATTEMPTS);
    }

    public static function login(School $school, string $type, int $id): void
    {
        session()->regenerate();
        session(['portal' => ['school_id' => $school->id, 'type' => $type, 'id' => $id]]);
    }

    public static function logout(): void
    {
        session()->forget('portal');
        session()->regenerate();
    }

    /** @return array{type:string,model:Staff|Student}|null */
    public static function current(?School $school = null): ?array
    {
        $p = session('portal');
        if (! $p || ($school && (int) $p['school_id'] !== (int) $school->id)) { return null; }
        $model = $p['type'] === 'staff' ? Staff::find($p['id']) : Student::find($p['id']);
        if (! $model || $model->status !== 'active') { return null; }
        return ['type' => $p['type'], 'model' => $model];
    }

    public static function setPin(string $type, int $id, string $pin): void
    {
        $hash = Hash::make($pin);
        if ($type === 'staff') { Staff::whereKey($id)->update(['pin_hash' => $hash, 'must_change_pin' => false]); }
        elseif ($type === 'parent') { Student::whereKey($id)->update(['parent_pin_hash' => $hash, 'parent_must_change_pin' => false]); }
        else { Student::whereKey($id)->update(['pin_hash' => $hash, 'must_change_pin' => false]); }
    }

    public static function mustChangePin(array $me): bool
    {
        return match ($me['type']) {
            'parent' => (bool) $me['model']->parent_must_change_pin,
            default => (bool) $me['model']->must_change_pin,
        };
    }
}
