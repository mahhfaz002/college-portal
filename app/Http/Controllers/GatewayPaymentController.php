<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\Invoice;
use App\Models\User;
use App\Services\PaystackService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Online payments via Paystack. Public for the application fee (applicant has
 * no account yet); the account is created only once that fee is confirmed.
 */
class GatewayPaymentController extends Controller
{
    public function __construct(private PaystackService $paystack) {}

    /** Redirect the payer to the gateway for an invoice. */
    public function initialize(Invoice $invoice)
    {
        if ($invoice->isPaid()) {
            return $this->afterPaid($invoice);
        }

        try {
            $url = $this->paystack->initialize($invoice, route('payments.callback'));
        } catch (\Throwable $e) {
            return redirect()->route('home')->with('error', $e->getMessage());
        }

        return redirect()->away($url);
    }

    /** Paystack redirects here with ?reference= after payment. */
    public function callback(Request $request)
    {
        $reference = $request->query('reference') ?? $request->query('trxref');
        $invoice = Invoice::withoutGlobalScopes()->where('reference', $reference)->first();

        if (!$invoice) {
            return redirect()->route('home')->with('error', 'Unknown payment reference.');
        }

        if ($this->paystack->verify($invoice)) {
            return $this->afterPaid($invoice);
        }

        return redirect()->route('home')->with('error', 'Payment was not completed. Please try again.');
    }

    /**
     * Sandbox confirm — used only when no live keys are configured (non-prod).
     * Lets the whole flow be exercised before real Paystack keys are supplied.
     */
    public function sandbox(Invoice $invoice)
    {
        if (app()->environment('production')) {
            abort(404);
        }
        if (!$invoice->isPaid()) {
            $this->paystack->markPaid($invoice, 'SANDBOX-'.Str::upper(Str::random(8)), 'sandbox');
        }

        return $this->afterPaid($invoice);
    }

    /**
     * Post-payment routing by purpose. For the application fee we create the
     * applicant's limited account (if not already created) and log them in.
     */
    private function afterPaid(Invoice $invoice)
    {
        if ($invoice->purpose === 'application_fee' && $invoice->applicant_id) {
            $applicant = Applicant::withoutGlobalScopes()->find($invoice->applicant_id);
            $tempPassword = $this->ensureApplicantAccount($applicant);

            if ($applicant->user_id) {
                Auth::loginUsingId($applicant->user_id);
            }

            $msg = 'Application fee paid successfully. Your applicant account is ready.';
            if ($tempPassword) {
                $msg .= " Your login email is {$applicant->email} and temporary password is {$tempPassword} — please change it after logging in.";
            }

            return redirect()->route('dashboard')
                ->with('success', $msg)
                ->with('receipt_invoice_id', $invoice->id);
        }

        // Other fee types are handled in later phases; default back to dashboard.
        return redirect()->to(Auth::check() ? route('dashboard') : route('home'))
            ->with('success', 'Payment confirmed.');
    }

    /**
     * Create the limited applicant User the first time, mark the application
     * submitted, and return the temporary password (null if it already existed).
     */
    private function ensureApplicantAccount(Applicant $applicant): ?string
    {
        $applicant->update([
            'payment_status'     => 'paid',
            'application_status' => 'submitted',
            'status'             => 'pending',
        ]);

        if ($applicant->user_id) {
            return null;
        }

        $tempPassword = Str::random(10);
        $user = User::create([
            'name'                 => $applicant->full_name,
            'email'                => $applicant->email,
            'password'             => Hash::make($tempPassword),
            'role'                 => 'applicant',
            'college_id'           => $applicant->college_id,
            'must_change_password' => true,
        ]);

        $applicant->update(['user_id' => $user->id]);

        return $tempPassword;
    }
}
