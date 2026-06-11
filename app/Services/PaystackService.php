<?php

namespace App\Services;

use App\Models\College;
use App\Models\Invoice;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Thin Paystack integration built on Laravel's HTTP client (no SDK needed).
 *
 * Key resolution: the paying college's own secret key (College->paystack_secret_key)
 * takes precedence, falling back to services.paystack.secret_key. When NO secret
 * key is configured and the app is not in production, initialize() returns a
 * sandbox URL that auto-confirms the payment — so the whole application→payment
 * →account flow can be exercised before live keys are provided.
 */
class PaystackService
{
    public function secretKey(?College $college = null): ?string
    {
        return ($college?->paystack_secret_key)
            ?: config('services.paystack.secret_key');
    }

    public function publicKey(?College $college = null): ?string
    {
        return ($college?->paystack_public_key)
            ?: config('services.paystack.public_key');
    }

    public function isLive(?College $college = null): bool
    {
        return !empty($this->secretKey($college));
    }

    /**
     * Naira amount grossed up so the PAYER bears Paystack's transaction fee.
     * NG card fee = 1.5% + ₦100 (₦100 waived under ₦2,500), capped at ₦2,000.
     */
    public static function grossUpForFees(float $amount): float
    {
        $rate = 0.015;
        $flat = $amount < 2500 ? 0 : 100;
        $gross = ($amount + $flat) / (1 - $rate);
        $fee = $gross - $amount;
        if ($fee > 2000) {            // fee is capped, so add the cap directly
            $gross = $amount + 2000;
        }
        return ceil($gross);
    }

    /**
     * Begin a payment for an invoice. Returns a URL to redirect the payer to.
     * When $platform is true the PLATFORM owner's keys are used regardless of
     * the college (used for the student onboarding fee).
     */
    /** Platform-level invoices (onboarding fee) settle to the platform owner. */
    public function isPlatformInvoice(Invoice $invoice): bool
    {
        return $invoice->purpose === 'platform_registration';
    }

    /**
     * The secret key that owns a given invoice's transaction — platform key for
     * platform invoices, otherwise the college key (falling back to platform).
     * Used to verify the webhook signature. Null invoice → platform/default key.
     */
    public function secretForInvoice(?Invoice $invoice): ?string
    {
        if (!$invoice || $this->isPlatformInvoice($invoice)) {
            return config('services.paystack.secret_key');
        }
        $college = $invoice->college_id ? College::withoutGlobalScopes()->find($invoice->college_id) : null;
        return $this->secretKey($college);
    }

    public function initialize(Invoice $invoice, string $callbackUrl): string
    {
        $college = $invoice->college_id ? College::withoutGlobalScopes()->find($invoice->college_id) : null;
        $secret = $this->isPlatformInvoice($invoice)
            ? config('services.paystack.secret_key')
            : $this->secretKey($college);

        // Sandbox fallback (no keys yet) — only outside production.
        if (empty($secret)) {
            if (app()->environment('production')) {
                throw new \RuntimeException('Payment gateway is not configured. Please contact the college.');
            }
            $invoice->update(['payment_method' => 'sandbox']);
            return route('payments.sandbox', ['invoice' => $invoice->id]);
        }

        $response = Http::withToken($secret)
            ->acceptJson()
            ->post(rtrim(config('services.paystack.base_url'), '/') . '/transaction/initialize', [
                'email'        => $invoice->payer_email,
                'amount'       => (int) round(((float) $invoice->amount) * 100), // kobo
                'reference'    => $invoice->reference,
                'callback_url' => $callbackUrl,
                'metadata'     => [
                    'invoice_id' => $invoice->id,
                    'purpose'    => $invoice->purpose,
                ],
            ]);

        if (!$response->ok() || !($response->json('status'))) {
            throw new \RuntimeException('Could not start payment: ' . $response->json('message', 'gateway error'));
        }

        $invoice->update([
            'payment_method'    => 'paystack',
            'gateway_reference' => $response->json('data.reference'),
        ]);

        return $response->json('data.authorization_url');
    }

    /**
     * Verify a transaction by reference and mark the invoice paid on success.
     */
    public function verify(Invoice $invoice): bool
    {
        $college = $invoice->college_id ? College::withoutGlobalScopes()->find($invoice->college_id) : null;
        $secret = $this->isPlatformInvoice($invoice)
            ? config('services.paystack.secret_key')
            : $this->secretKey($college);

        if (empty($secret)) {
            // Sandbox: nothing to verify against the gateway.
            return $invoice->isPaid();
        }

        $response = Http::withToken($secret)
            ->acceptJson()
            ->get(rtrim(config('services.paystack.base_url'), '/') . '/transaction/verify/' . $invoice->reference);

        $success = $response->ok()
            && $response->json('status')
            && $response->json('data.status') === 'success';

        if ($success && !$invoice->isPaid()) {
            $this->markPaid($invoice, $response->json('data.reference'), 'paystack');
        }

        return $success;
    }

    public function markPaid(Invoice $invoice, ?string $gatewayRef = null, string $method = 'paystack'): void
    {
        $invoice->update([
            'status'            => 'paid',
            'gateway_reference' => $gatewayRef ?? $invoice->gateway_reference,
            'payment_method'    => $method,
            'paid_at'           => now(),
        ]);
    }

    public static function reference(string $prefix = 'INV'): string
    {
        return strtoupper($prefix) . '-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(6));
    }
}
