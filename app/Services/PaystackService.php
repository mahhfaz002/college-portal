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
     * Begin a payment for an invoice. Returns a URL to redirect the payer to.
     */
    public function initialize(Invoice $invoice, string $callbackUrl): string
    {
        $college = $invoice->college_id ? College::withoutGlobalScopes()->find($invoice->college_id) : null;
        $secret = $this->secretKey($college);

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
        $secret = $this->secretKey($college);

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
