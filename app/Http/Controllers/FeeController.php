<?php

namespace App\Http\Controllers;

use App\Models\FeeStructure;
use App\Models\Payment;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentFee;
use App\Services\Audit;
use App\Services\Fees;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class FeeController extends Controller
{
    public function index(Request $r, School $school)
    {
        $structures = FeeStructure::where('school_id', $school->id)->latest()->get();
        $q = StudentFee::where('school_id', $school->id)->with(['student', 'structure'])->whereHas('student');
        if ($r->query('show') !== 'all') { $q->where('status', '!=', 'paid'); }
        $owing = $q->latest()->limit(200)->get();
        return view('school.fees', [
            'structures' => $structures, 'owing' => $owing, 'classes' => $school->classes,
            'collected' => Payment::where('school_id', $school->id)->where('status', 'success')->sum('amount'),
            'outstanding' => StudentFee::where('school_id', $school->id)->selectRaw('COALESCE(SUM(amount_due - amount_paid),0) o')->value('o'),
            'recent' => Payment::where('school_id', $school->id)->where('status', 'success')->with('fee.student')->latest()->limit(10)->get(),
        ]);
    }

    public function store(Request $r, School $school)
    {
        $d = $r->validate(['label' => 'required|max:190', 'class_name' => 'nullable|max:100', 'session_label' => 'required', 'term' => 'required|in:First Term,Second Term,Third Term', 'amount' => 'required|numeric|min:0']);
        $f = FeeStructure::create($d + ['school_id' => $school->id, 'class_name' => ($d['class_name'] ?? null) ?: null]);
        $n = 0;
        foreach (Student::where('school_id', $school->id)->active()->when($f->class_name, fn ($q) => $q->where('class_name', $f->class_name))->get() as $s) {
            StudentFee::firstOrCreate(['student_id' => $s->id, 'fee_structure_id' => $f->id], ['school_id' => $school->id, 'amount_due' => $f->amount]); $n++;
        }
        Audit::by($this->me(), 'fee.created', "{$f->label} for $n students");
        return back()->with('ok', "Fee created and billed to $n students.");
    }

    public function destroy(School $school, $id)
    {
        FeeStructure::where('school_id', $school->id)->whereKey($id)->delete();
        return back()->with('ok', 'Fee removed.');
    }

    /** Cash / bank-transfer payment recorded by the school. */
    public function record(Request $r, School $school)
    {
        $d = $r->validate(['student_fee_id' => 'required|integer', 'amount' => 'required|numeric|min:1', 'method' => 'required|in:cash,transfer']);
        $fee = StudentFee::where('school_id', $school->id)->findOrFail($d['student_fee_id']);
        $amount = min((float) $d['amount'], $fee->balance);
        if ($amount <= 0) { return back()->with('err', 'That fee is already settled.'); }
        Payment::create(['school_id' => $school->id, 'student_fee_id' => $fee->id, 'reference' => strtoupper('MAN-' . Str::random(10)), 'amount' => $amount, 'method' => $d['method'], 'status' => 'success', 'recorded_by' => $this->staff()->name]);
        Fees::apply($fee, $amount);
        Audit::by($this->me(), 'fee.recorded', "{$d['method']} ₦$amount fee #{$fee->id}");
        return back()->with('ok', 'Payment recorded.');
    }

    /** Parent/student pays through the school's own Paystack account. */
    public function pay(Request $r, School $school, $fee)
    {
        $me = $this->me();
        $fee = StudentFee::where('school_id', $school->id)->where('student_id', $me['model']->id)->findOrFail($fee);
        if (! $school->paystack_public_key || ! $school->paystack_secret_key) { return back()->with('err', 'Online payment is not set up for this school. Please pay at the school office.'); }
        if ($fee->balance <= 0) { return back()->with('err', 'Nothing left to pay.'); }
        $amount = $r->filled('amount') ? min((float) $r->input('amount'), $fee->balance) : $fee->balance;
        $ref = 'FEE-' . strtoupper(Str::random(14));
        $res = Http::withToken($school->paystack_secret_key)->post('https://api.paystack.co/transaction/initialize', [
            'email' => $me['model']->guardian_email ?: ($school->email ?: 'noreply@example.com'), 'amount' => (int) round($amount * 100), 'reference' => $ref, 'callback_url' => route('pay.callback', $school),
        ]);
        if (! $res->ok() || ! data_get($res->json(), 'data.authorization_url')) { return back()->with('err', 'Could not start the payment. Please try again.'); }
        Payment::create(['school_id' => $school->id, 'student_fee_id' => $fee->id, 'reference' => $ref, 'amount' => $amount, 'method' => 'paystack', 'status' => 'pending']);
        return redirect()->away($res->json('data.authorization_url'));
    }

    public function callback(Request $r, School $school)
    {
        $pay = Payment::where('school_id', $school->id)->where('reference', (string) ($r->query('reference') ?: $r->query('trxref')))->first();
        if ($pay && $pay->status !== 'success' && Fees::verify((string) $school->paystack_secret_key, $pay->reference)) { Fees::settle($pay); }
        return redirect()->route('family.fees', $school)->with($pay && $pay->fresh()->status === 'success' ? 'ok' : 'err', $pay && $pay->fresh()->status === 'success' ? 'Payment received. Thank you!' : 'We could not confirm that payment yet. If you were charged it will reflect shortly.');
    }
}
