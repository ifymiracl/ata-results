<?php

namespace App\Http\Controllers;

use App\Models\School;
use App\Models\SubscriptionPayment;
use App\Services\Billing;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class BillingController extends Controller
{
    public function index(School $school)
    {
        return view('school.billing', [
            'count' => Billing::activeStudents($school), 'price' => Billing::pricePerStudent(), 'due' => Billing::amountDue($school),
            'configured' => Billing::configured(), 'history' => SubscriptionPayment::where('school_id', $school->id)->latest()->limit(10)->get(),
        ]);
    }

    public function pay(School $school)
    {
        if (! Billing::configured()) { return back()->with('err', 'Billing is not available yet. Please contact the platform owner.'); }
        $due = Billing::amountDue($school);
        if ($due <= 0) { return back()->with('err', 'There are no active students to bill yet.'); }
        $ref = 'SUB-' . strtoupper(Str::random(14));
        $res = Http::withToken(\App\Models\Setting::get('paystack_secret'))->post('https://api.paystack.co/transaction/initialize', [
            'email' => $this->staff()->email ?: ($school->email ?: 'billing@example.com'), 'amount' => (int) round($due * 100), 'reference' => $ref, 'callback_url' => route('admin.billing.callback', $school),
        ]);
        if (! $res->ok() || ! $res->json('data.authorization_url')) { return back()->with('err', 'Could not start the payment. Please try again.'); }
        SubscriptionPayment::create(['school_id' => $school->id, 'reference' => $ref, 'student_count' => Billing::activeStudents($school), 'amount' => $due]);
        return redirect()->away($res->json('data.authorization_url'));
    }

    public function callback(Request $r, School $school)
    {
        $p = SubscriptionPayment::where('school_id', $school->id)->where('reference', (string) ($r->query('reference') ?: $r->query('trxref')))->first();
        if ($p && $p->status !== 'success' && Billing::verify($p->reference)) { Billing::settle($p); }
        $ok = $p && $p->fresh()->status === 'success';
        return redirect()->route('admin.billing', $school)->with($ok ? 'ok' : 'err', $ok ? 'Subscription paid. Thank you!' : 'We could not confirm the payment yet.');
    }
}
