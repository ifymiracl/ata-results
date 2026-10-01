<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\School;
use App\Models\Setting;
use App\Models\SubscriptionPayment;
use App\Services\Billing;
use App\Services\Fees;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    /** Paystack posts here for both school-fee payments (school keys) and platform subscriptions (platform keys). */
    public function paystack(Request $r)
    {
        $payload = $r->getContent();
        $sig = $r->header('x-paystack-signature');
        $ref = $r->input('data.reference');
        if (! $sig || ! $ref || $r->input('event') !== 'charge.success') { return response('ignored'); }

        $sub = SubscriptionPayment::where('reference', $ref)->first();
        if ($sub) {
            if (! hash_equals(hash_hmac('sha512', $payload, (string) Setting::get('paystack_secret')), $sig)) { return response('bad signature', 401); }
            Billing::settle($sub);
            return response('ok');
        }
        $pay = Payment::where('reference', $ref)->first();
        if ($pay) {
            $school = School::find($pay->school_id);
            if (! $school || ! hash_equals(hash_hmac('sha512', $payload, (string) $school->paystack_secret_key), $sig)) { return response('bad signature', 401); }
            Fees::settle($pay);
            return response('ok');
        }
        return response('unknown', 404);
    }
}
