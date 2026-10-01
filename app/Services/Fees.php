<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\StudentFee;
use Illuminate\Support\Facades\Http;

class Fees
{
    public static function settle(Payment $p): void
    {
        if ($p->status === 'success') { return; }
        $p->update(['status' => 'success']);
        if ($fee = StudentFee::find($p->student_fee_id)) { self::apply($fee, (float) $p->amount); }
    }

    public static function apply(StudentFee $fee, float $amount): void
    {
        $paid = min((float) $fee->amount_due, (float) $fee->amount_paid + $amount);
        $fee->update(['amount_paid' => $paid, 'status' => $paid >= $fee->amount_due ? 'paid' : ($paid > 0 ? 'partial' : 'unpaid')]);
    }

    public static function verify(string $secret, string $reference): bool
    {
        $res = Http::withToken($secret)->get('https://api.paystack.co/transaction/verify/' . urlencode($reference));
        return $res->ok() && data_get($res->json(), 'data.status') === 'success';
    }
}
