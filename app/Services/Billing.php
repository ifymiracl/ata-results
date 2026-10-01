<?php

namespace App\Services;

use App\Models\School;
use App\Models\Setting;
use App\Models\Student;
use App\Models\SubscriptionPayment;
use Illuminate\Support\Facades\Http;

/** Money paid TO THE PLATFORM for a school's per-student subscription. */
class Billing
{
    public static function pricePerStudent(): float { return (float) Setting::get('price_per_student', 100); }

    public static function configured(): bool { return Setting::get('paystack_public') && Setting::get('paystack_secret'); }

    public static function activeStudents(School $s): int { return Student::where('school_id', $s->id)->where('status', 'active')->count(); }

    public static function amountDue(School $s): float { return self::activeStudents($s) * self::pricePerStudent(); }

    public static function settle(SubscriptionPayment $p): void
    {
        if ($p->status === 'success') { return; }
        $p->update(['status' => 'success']);
        $school = School::find($p->school_id);
        $from = ($school->subscription_paid_until && $school->subscription_paid_until->isFuture()) ? $school->subscription_paid_until : now();
        $school->update(['subscription_paid_until' => $from->copy()->addDays((int) Setting::get('subscription_days', 120)), 'plan_name' => 'Subscribed']);
    }

    public static function verify(string $reference): bool
    {
        $res = Http::withToken((string) Setting::get('paystack_secret'))->get('https://api.paystack.co/transaction/verify/' . urlencode($reference));
        return $res->ok() && data_get($res->json(), 'data.status') === 'success';
    }
}
