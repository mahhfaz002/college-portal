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

    /**
     * Checkout / invoice review page shown BEFORE the gateway. Itemises the fee,
     * the flat portal convenience fee and the Paystack processing fee, then the
     * grand total. "Proceed to Payment" continues to initialize(). Public,
     * because the application fee is paid before the applicant has an account.
     */
    public function checkout(Invoice $invoice)
    {
        if ($invoice->isPaid()) {
            return $this->afterPaid($invoice);
        }

        $breakdown = $this->applySurcharges($invoice);

        $college = $invoice->college_id
            ? \App\Models\College::withoutGlobalScopes()->find($invoice->college_id)
            : current_college();

        return view('payments.checkout', compact('invoice', 'breakdown', 'college'));
    }

    /**
     * Compute and persist the surcharges for a pending invoice (idempotent), and
     * return the breakdown. Called by the checkout page and, defensively, by
     * initialize() so a direct gateway hit can never skip the surcharges.
     */
    private function applySurcharges(Invoice $invoice): array
    {
        $breakdown = PaystackService::surcharges((float) $invoice->amount);

        if (! $invoice->isPaid()) {
            // Keep the figures on the model so chargeable() is correct for THIS
            // request even when they can't be persisted.
            $invoice->convenience_fee = $breakdown['convenience'];
            $invoice->service_fee     = $breakdown['service'];

            // Persist only when the columns exist. Guards against a database that
            // hasn't run the surcharge migration yet (e.g. a production deploy
            // whose deploy command skipped `php artisan migrate --force`), so the
            // checkout / invoice page can never hard-500 over a missing column.
            try {
                if (\Illuminate\Support\Facades\Schema::hasColumn('invoices', 'convenience_fee')
                    && \Illuminate\Support\Facades\Schema::hasColumn('invoices', 'service_fee')) {
                    $invoice->save();
                }
            } catch (\Throwable $e) {
                // Non-fatal: the breakdown is computed in-memory and payment can
                // still proceed; the columns simply aren't stored until migrated.
            }
        }

        return $breakdown;
    }

    /** Redirect the payer to the gateway for an invoice. */
    public function initialize(Invoice $invoice)
    {
        if ($invoice->isPaid()) {
            return $this->afterPaid($invoice);
        }

        $this->applySurcharges($invoice);

        try {
            $url = $this->paystack->initialize($invoice, route('payments.callback'));
        } catch (\Throwable $e) {
            // Keep the payer in the payment flow with a clear reason rather than
            // silently dumping them on the landing page.
            return redirect()->route('payments.checkout', $invoice)
                ->with('error', $e->getMessage());
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
     * Server-to-server webhook. Paystack POSTs charge.success here; we verify
     * the signature against the invoice's own secret key, mark it paid and run
     * the same fulfilment as the browser callback (idempotent). This is the
     * source of truth — the callback is only for redirecting the user back.
     */
    public function webhook(Request $request)
    {
        $payload   = $request->getContent();
        $signature = $request->header('x-paystack-signature');
        $event     = json_decode($payload, true) ?: [];
        $type      = $event['event'] ?? 'unknown';
        $data      = $event['data'] ?? [];
        $reference = $data['reference'] ?? null;

        $invoice = $reference
            ? Invoice::withoutGlobalScopes()->where('reference', $reference)
                ->orWhere('gateway_reference', $reference)->first()
            : null;

        // Verify HMAC-SHA512 of the RAW body against the owning account's secret
        // (the per-invoice secret, or the platform master — marketplace webhooks
        // arrive on the master account).
        $secret = $this->paystack->secretForInvoice($invoice);
        $master = $this->paystack->masterSecret();
        $valid  = $signature && (
            ($secret && hash_equals(hash_hmac('sha512', $payload, $secret), $signature)) ||
            ($master && hash_equals(hash_hmac('sha512', $payload, $master), $signature))
        );

        // Idempotency + replay protection: one row per (event + Paystack id/ref).
        // Guarded so a deploy that hasn't run the migration yet still processes.
        $log = null;
        if (\Illuminate\Support\Facades\Schema::hasTable('paystack_webhook_events')) {
            $dedupe = $type . ':' . ($data['id'] ?? $reference ?? md5($payload));
            $log = \App\Models\PaystackWebhookEvent::firstOrNew(['dedupe_key' => $dedupe]);
            if ($log->exists && $log->status === 'processed') {
                return response()->json(['status' => true, 'note' => 'duplicate ignored']);
            }
            $log->fill([
                'event'           => $type,
                'reference'       => $reference,
                'college_id'      => $invoice?->college_id,
                'signature_valid' => (bool) $valid,
                'payload'         => $event,
                'attempts'        => (int) ($log->attempts ?? 0) + 1,
                'status'          => $valid ? 'received' : 'failed',
                'error'           => $valid ? null : 'invalid signature',
            ])->save();
        }

        if (!$valid) {
            \Log::warning('Paystack webhook: invalid signature', ['event' => $type, 'reference' => $reference]);
            return response()->json(['status' => false], 401);
        }

        try {
            $this->processWebhookEvent($type, $data, $invoice);
            $log?->markProcessed();
        } catch (\Throwable $e) {
            // Acknowledge 200 (so Paystack doesn't hammer-retry) but keep the
            // event flagged 'failed' in the log for our own retry.
            \Log::error('Paystack webhook processing failed', ['event' => $type, 'reference' => $reference, 'error' => $e->getMessage()]);
            $log?->markFailed($e->getMessage());
        }

        return response()->json(['status' => true]);
    }

    /**
     * Idempotent handling of a verified webhook event. Pure (no signature/HTTP
     * concerns) so it can be re-run from the event log or moved to a queued job.
     */
    public function processWebhookEvent(string $type, array $data, ?Invoice $invoice): void
    {
        switch ($type) {
            case 'charge.success':
                if ($invoice && !$invoice->isPaid()) {
                    $this->paystack->markPaid($invoice, $data['reference'] ?? null, 'paystack', $data);
                    $this->fulfill($invoice);
                }
                break;

            case 'transfer.success':   // settlement payout to a subaccount succeeded
                if ($invoice) {
                    $invoice->update([
                        'settlement_status'    => 'settled',
                        'settlement_reference' => $data['reference'] ?? $data['transfer_code'] ?? $invoice->settlement_reference,
                        'settlement_at'        => now(),
                    ]);
                }
                break;

            case 'transfer.failed':
                if ($invoice) {
                    $invoice->update(['settlement_status' => 'failed']);
                }
                break;

            // Any other event type is logged and acknowledged only.
        }
    }

    /**
     * Idempotent side effects for a paid invoice (account creation, admission
     * progression, registration). Shared by the callback, sandbox and webhook.
     */
    private function fulfill(Invoice $invoice): void
    {
        if ($invoice->purpose === 'platform_registration' && $invoice->user_id) {
            User::withoutGlobalScopes()->where('id', $invoice->user_id)->update(['platform_fee_paid' => true]);
            return;
        }

        // Change-of-course fee: forward the application to the Academic Secretary.
        if ($invoice->purpose === 'change_of_course') {
            \App\Http\Controllers\ChangeOfCourseController::markPaidByInvoice($invoice);
            return;
        }

        $applicant = $invoice->applicant_id ? Applicant::withoutGlobalScopes()->find($invoice->applicant_id) : null;
        if (!$applicant) {
            return;
        }

        if ($invoice->purpose === 'application_fee') {
            $this->ensureApplicantAccount($applicant);
        } elseif ($invoice->purpose === 'acceptance_fee') {
            $applicant->update(['application_status' => 'accepted']);
            $this->ensureRegistrationInvoice($applicant);
        } elseif ($invoice->purpose === 'registration_fee') {
            $this->completeRegistration($applicant);
        }
    }

    /**
     * Post-payment routing for the browser flow: run fulfilment, then log the
     * payer in (where applicable) and redirect with a friendly message.
     */
    private function afterPaid(Invoice $invoice)
    {
        $this->fulfill($invoice);
        $applicant = $invoice->applicant_id ? Applicant::withoutGlobalScopes()->find($invoice->applicant_id) : null;

        if ($invoice->purpose === 'platform_registration' && $invoice->user_id) {
            Auth::loginUsingId($invoice->user_id);
            return redirect()->route('dashboard')->with('success',
                'Platform registration fee paid. Welcome — your student account is now active.');
        }

        if ($invoice->purpose === 'application_fee' && $applicant) {
            if ($applicant->user_id) {
                Auth::loginUsingId($applicant->user_id);
            }
            return redirect()->route('dashboard')->with('success',
                "Application fee paid successfully. Your applicant account is ready (login email {$applicant->email}). If this is your first login, use the temporary password emailed/shown to you and change it.");
        }

        if ($invoice->purpose === 'acceptance_fee' && $applicant) {
            return redirect()->route('dashboard')->with('success',
                'Acceptance fee paid. You can now download your admission letter and pay your registration fee.');
        }

        if ($invoice->purpose === 'registration_fee' && $applicant) {
            return redirect()->route('dashboard')->with('success',
                'Registration fee paid. Your student dashboard is unlocked — please upload your documents to complete registration.');
        }

        if ($invoice->purpose === 'change_of_course' && Auth::check()) {
            return redirect()->route('change-of-course.index')
                ->with('success', 'Application fee paid. Your change-of-course request has been forwarded to the Academic Secretary for review.');
        }

        // General student fees (bursar payment orders): straight to the printable
        // receipt for what they just paid, per the portal payment flow.
        if (Auth::check()) {
            return redirect()->route('invoices.receipt', $invoice)
                ->with('success', 'Payment confirmed.');
        }

        return redirect()->route('home')->with('success', 'Payment confirmed.');
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
            'email_verified_at'    => now(),   // identity confirmed via the paid application fee
        ]);

        $applicant->update(['user_id' => $user->id]);

        return $tempPassword;
    }

    /** Raise the registration-fee invoice for an admitted+accepted applicant. */
    private function ensureRegistrationInvoice(Applicant $applicant): void
    {
        $program = \App\Models\Program::withoutGlobalScopes()->find($applicant->admitted_program_id);
        if (!$program) {
            return;
        }

        $exists = Invoice::withoutGlobalScopes()
            ->where('applicant_id', $applicant->id)
            ->where('purpose', 'registration_fee')
            ->whereIn('status', ['pending', 'paid'])
            ->exists();

        if ($exists) {
            return;
        }

        Invoice::create([
            'college_id'  => $applicant->college_id,
            'applicant_id'=> $applicant->id,
            'user_id'     => $applicant->user_id,
            'program_id'  => $program->id,
            'purpose'     => 'registration_fee',
            'description' => 'Registration fee (First Semester, 100 Level) — '.$program->name,
            'amount'      => $program->firstSemesterRegistrationFee(),
            'payer_email' => $applicant->email,
            'status'      => 'pending',
            'reference'   => \App\Services\PaystackService::reference('REG'),
        ]);
    }

    /**
     * Create the Student record, assign the registration number and promote the
     * applicant's account to a full student (dashboard unlocks immediately;
     * "fully registered" still requires HOD approval of uploaded documents).
     */
    private function completeRegistration(Applicant $applicant): void
    {
        $program = \App\Models\Program::withoutGlobalScopes()->with('department')->find($applicant->admitted_program_id);
        if (!$program) {
            return;
        }

        $student = \App\Models\Student::withoutGlobalScopes()
            ->where('email', $applicant->email)->first();

        if (!$student) {
            $regNumber = app(\App\Services\StudentIdGenerator::class)->generate($program);

            $student = \App\Models\Student::create([
                'full_name'           => $applicant->full_name,
                'email'               => $applicant->email,
                'admission_number'    => $applicant->admission_number,
                'registration_number' => $regNumber,
                'college_id'          => $applicant->college_id,
                'department_id'       => $program->department_id,
                'program_id'          => $program->id,
                'level'               => '100',
                'class_arm'           => $program->name,
                'parent_phone'        => $applicant->guardian_phone ?? $applicant->parent_phone,
                'fees_balance'        => 0,
                'photo'               => $applicant->passport,
                'applicant_id'        => $applicant->id,
                'registration_status' => 'registration_paid',
            ]);
        }

        $applicant->update(['application_status' => 'registered']);

        if ($applicant->user_id) {
            \App\Models\User::withoutGlobalScopes()->where('id', $applicant->user_id)
                ->update(['role' => 'student']);
        }
    }
}
