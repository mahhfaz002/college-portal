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
    /** Flat portal convenience fee charged on every online transaction (Naira). */
    public const CONVENIENCE_FEE = 1000;

    /**
     * Reserved / non-routable top-level domains Paystack rejects outright with
     * "Invalid Email Address Passed" (e.g. the seeded *@*.test accounts). An
     * invoice carrying one of these can never reach the gateway, so we treat it
     * as undeliverable and fall back to a real address.
     */
    private const RESERVED_EMAIL_TLDS = ['test', 'local', 'localhost', 'example', 'invalid', 'internal'];

    /**
     * Whether an address is well-formed AND something the gateway will accept
     * (real, routable TLD — not one of the reserved test domains above).
     */
    public function isDeliverableEmail(?string $email): bool
    {
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        $domain = strtolower(substr(strrchr($email, '@'), 1));
        $tld    = strrchr($domain, '.');
        $tld    = $tld ? ltrim($tld, '.') : $domain;

        return !in_array($tld, self::RESERVED_EMAIL_TLDS, true);
    }

    /**
     * Resolve a gateway-acceptable payer email for an invoice. Prefers the
     * invoice's own payer email, then the linked user, then the college's
     * contact address, then a configured platform fallback. Throws a clear,
     * user-facing message only when nothing deliverable can be found — so a
     * bad email surfaces a fixable error instead of a silent gateway failure.
     */
    public function payerEmail(Invoice $invoice, ?College $college = null): string
    {
        $college ??= $invoice->college_id
            ? College::withoutGlobalScopes()->find($invoice->college_id)
            : null;

        $user = $invoice->user_id
            ? \App\Models\User::withoutGlobalScopes()->find($invoice->user_id)
            : null;

        $candidates = [
            $invoice->payer_email,
            $user?->email,
            $college?->email,
            config('services.paystack.fallback_email'),
        ];

        foreach ($candidates as $email) {
            if ($this->isDeliverableEmail($email)) {
                return $email;
            }
        }

        throw new \RuntimeException(
            'Your email address on file is not valid for online payment. '
            . 'Please update your profile email to a real address (e.g. a Gmail) and try again.'
        );
    }

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
     * Itemised surcharges for an online payment of $base Naira.
     *
     * The portal convenience fee is added first, then Paystack's processing fee
     * is grossed up over (base + convenience) so the PAYER bears the full gateway
     * cost and the college/platform still nets the base + convenience amounts.
     *
     * @return array{base: float, convenience: float, service: float, total: float}
     */
    public static function surcharges(float $base): array
    {
        $convenience = (float) self::CONVENIENCE_FEE;
        $subtotal    = $base + $convenience;
        $total       = self::grossUpForFees($subtotal);   // adds the Paystack fee
        $service     = $total - $subtotal;

        return [
            'base'        => round($base, 2),
            'convenience' => round($convenience, 2),
            'service'     => round($service, 2),
            'total'       => round($total, 2),
        ];
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

    // =====================================================================
    //  Marketplace / Subaccount layer
    // =====================================================================

    /** The platform's MASTER Paystack secret key (subaccounts live under it). */
    public function masterSecret(): ?string
    {
        return config('services.paystack.secret_key');
    }

    private function endpoint(string $path): string
    {
        return rtrim(config('services.paystack.base_url'), '/') . $path;
    }

    /**
     * Which secret owns a transaction:
     *   - platform invoices                       → master account
     *   - a college that settles via a subaccount → master account (the
     *     subaccount lives under it; the split routes its share automatically)
     *   - a college with its OWN standalone key (legacy) and no subaccount → that key
     *   - otherwise                                → master account
     * Used by initialize(), verify() AND webhook signature verification so they
     * always agree on the owning account.
     */
    public function resolveSecret(?College $college, ?Invoice $invoice = null): ?string
    {
        if ($invoice && $this->isPlatformInvoice($invoice)) {
            return $this->masterSecret();
        }
        if ($college && $college->usesSubaccount()) {
            return $this->masterSecret();
        }
        if ($college && !empty($college->paystack_secret_key)) {
            return $college->paystack_secret_key;
        }
        return $this->masterSecret();
    }

    /**
     * Create — or update if one already exists — the college's Paystack
     * subaccount. Idempotent and safe to rerun. `percentage_charge` is the
     * platform's commission for THIS institution (Paystack-native split, so no
     * manual settlement maths). Returns the Paystack `data` array; throws on error.
     *
     * @param array{settlement_bank:string, account_number:string, business_name?:string, percentage_charge?:float} $details
     */
    public function createOrUpdateSubaccount(College $college, array $details): array
    {
        $secret = $this->masterSecret();
        if (empty($secret)) {
            throw new \RuntimeException('Platform Paystack secret key is not configured.');
        }

        $payload = [
            'business_name'         => $details['business_name'] ?? $college->name,
            'settlement_bank'       => $details['settlement_bank'],
            'account_number'        => $details['account_number'],
            'percentage_charge'     => (float) ($details['percentage_charge']
                                        ?? $college->commission_percentage
                                        ?? config('services.paystack.default_commission_percentage', 2)),
            'primary_contact_email' => $college->email,
        ];

        $http = Http::withToken($secret)->acceptJson();
        $resp = $college->paystack_subaccount_code
            ? $http->put($this->endpoint('/subaccount/' . $college->paystack_subaccount_code), $payload)
            : $http->post($this->endpoint('/subaccount'), $payload);

        if (!$resp->ok() || !$resp->json('status')) {
            \Log::warning('Paystack subaccount failed', ['college' => $college->id, 'body' => $resp->json()]);
            throw new \RuntimeException('Paystack subaccount error: ' . $resp->json('message', 'request failed'));
        }

        $data = $resp->json('data');
        $college->forceFill([
            'paystack_subaccount_code'   => $data['subaccount_code'] ?? $college->paystack_subaccount_code,
            'paystack_subaccount_name'   => $data['business_name'] ?? $payload['business_name'],
            'settlement_account_name'    => $data['account_name'] ?? $college->settlement_account_name,
            'paystack_subaccount_status' => 'active',
        ])->save();

        return $data;
    }

    /** Refresh a college's subaccount details/status from Paystack. */
    public function fetchSubaccount(College $college): ?array
    {
        if (!$college->paystack_subaccount_code || empty($this->masterSecret())) {
            return null;
        }
        $resp = Http::withToken($this->masterSecret())->acceptJson()
            ->get($this->endpoint('/subaccount/' . $college->paystack_subaccount_code));

        if (!$resp->ok() || !$resp->json('status')) {
            return null;
        }
        $data = $resp->json('data');
        $college->forceFill([
            'paystack_subaccount_name'   => $data['business_name'] ?? $college->paystack_subaccount_name,
            'settlement_account_number'  => $data['account_number'] ?? $college->settlement_account_number,
            'settlement_bank'            => $data['settlement_bank'] ?? $college->settlement_bank,
            'commission_percentage'      => $data['percentage_charge'] ?? $college->commission_percentage,
            'paystack_subaccount_status' => (($data['active'] ?? true)) ? 'active' : 'inactive',
        ])->save();

        return $data;
    }

    /** List of banks for the settlement-account dropdown (cached 1 day). */
    public function banks(string $country = 'nigeria'): array
    {
        if (empty($this->masterSecret())) {
            return [];
        }
        return \Illuminate\Support\Facades\Cache::remember('paystack_banks_' . $country, now()->addDay(), function () use ($country) {
            $resp = Http::withToken($this->masterSecret())->acceptJson()
                ->get($this->endpoint('/bank'), ['country' => $country, 'perPage' => 100]);
            return ($resp->ok() && $resp->json('status')) ? ($resp->json('data') ?? []) : [];
        });
    }

    /** Resolve & validate a settlement account → returns the account name or null. */
    public function resolveBankAccount(string $bankCode, string $accountNumber): ?string
    {
        if (empty($this->masterSecret())) {
            return null;
        }
        $resp = Http::withToken($this->masterSecret())->acceptJson()
            ->get($this->endpoint('/bank/resolve'), ['account_number' => $accountNumber, 'bank_code' => $bankCode]);

        return ($resp->ok() && $resp->json('status')) ? $resp->json('data.account_name') : null;
    }

    /**
     * Record the settlement split on a paid invoice (idempotent). Only colleges
     * settling via a subaccount are split; the platform keeps its commission %
     * of the base fee plus the flat convenience fee, the institution keeps the rest.
     * These are indicative figures for reporting — Paystack performs the real split.
     */
    public function recordSettlement(Invoice $invoice, array $gatewayData = []): void
    {
        if ($invoice->institution_share !== null && empty($gatewayData)) {
            return; // already recorded
        }

        $college = $invoice->college_id ? College::withoutGlobalScopes()->find($invoice->college_id) : null;
        $base    = (float) $invoice->amount;

        if ($college && $college->usesSubaccount()) {
            $pct        = (float) ($college->commission_percentage ?? config('services.paystack.default_commission_percentage', 2));
            $commission = round($base * $pct / 100, 2);
            $share      = round($base - $commission, 2);
        } else {
            $commission = 0.0;
            $share      = $base;
        }

        $invoice->update([
            'platform_commission' => $commission + (float) ($invoice->convenience_fee ?? 0),
            'institution_share'   => $share,
            'settlement_status'   => $invoice->settlement_status ?? 'pending',
            'gateway_response'    => $gatewayData ?: $invoice->gateway_response,
        ]);
    }

    /**
     * The secret key that owns a given invoice's transaction. Used to verify
     * the webhook signature so it agrees with how the transaction was initialised.
     */
    public function secretForInvoice(?Invoice $invoice): ?string
    {
        if (!$invoice) {
            return $this->masterSecret();
        }
        $college = $invoice->college_id ? College::withoutGlobalScopes()->find($invoice->college_id) : null;
        return $this->resolveSecret($college, $invoice);
    }

    public function initialize(Invoice $invoice, string $callbackUrl): string
    {
        $college = $invoice->college_id ? College::withoutGlobalScopes()->find($invoice->college_id) : null;
        $secret  = $this->resolveSecret($college, $invoice);

        // Sandbox fallback (no keys yet) — only outside production.
        if (empty($secret)) {
            if (app()->environment('production')) {
                throw new \RuntimeException('Payment gateway is not configured. Please contact the college.');
            }
            $invoice->update(['payment_method' => 'sandbox']);
            return route('payments.sandbox', ['invoice' => $invoice->id]);
        }

        $payload = [
            'email'        => $this->payerEmail($invoice, $college),
            'amount'       => (int) round($invoice->chargeable() * 100), // kobo (fee + surcharges)
            'reference'    => $invoice->reference,
            'callback_url' => $callbackUrl,
            'metadata'     => [
                'invoice_id' => $invoice->id,
                'purpose'    => $invoice->purpose,
                'college_id' => $invoice->college_id,
            ],
        ];

        // Marketplace split: route the institution's share to ITS OWN subaccount
        // only (never another college's). Platform invoices stay on the master.
        if (!$this->isPlatformInvoice($invoice) && $college && $college->usesSubaccount()) {
            $payload['subaccount'] = $college->paystack_subaccount_code;
            $payload['bearer']     = config('services.paystack.subaccount_bearer', 'account');
        }

        $response = Http::withToken($secret)->acceptJson()
            ->post($this->endpoint('/transaction/initialize'), $payload);

        if (!$response->ok() || !($response->json('status'))) {
            \Log::warning('Paystack initialize failed', ['invoice' => $invoice->id, 'body' => $response->json()]);
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
     * Validates status, reference, amount and currency before crediting.
     */
    public function verify(Invoice $invoice): bool
    {
        $college = $invoice->college_id ? College::withoutGlobalScopes()->find($invoice->college_id) : null;
        $secret  = $this->resolveSecret($college, $invoice);

        if (empty($secret)) {
            return $invoice->isPaid(); // sandbox: nothing to verify
        }

        $response = Http::withToken($secret)->acceptJson()
            ->get($this->endpoint('/transaction/verify/' . $invoice->reference));

        $data = $response->json('data') ?? [];

        $success = $response->ok()
            && $response->json('status')
            && (($data['status'] ?? null) === 'success')
            && (($data['reference'] ?? null) === $invoice->reference)                       // reference match
            && ((int) ($data['amount'] ?? 0) >= (int) round($invoice->chargeable() * 100))   // amount >= charged
            && (($data['currency'] ?? 'NGN') === ($invoice->currency ?: 'NGN'));             // currency match

        if ($success && !$invoice->isPaid()) {
            $this->markPaid($invoice, $data['reference'] ?? null, 'paystack', $data);
        }

        return $success;
    }

    /**
     * Mark an invoice paid and record the settlement split. Idempotent — callers
     * already guard on !isPaid(); recording is keyed off institution_share.
     */
    public function markPaid(Invoice $invoice, ?string $gatewayRef = null, string $method = 'paystack', array $gatewayData = []): void
    {
        $invoice->update([
            'status'            => 'paid',
            'gateway_reference' => $gatewayRef ?? $invoice->gateway_reference,
            'payment_method'    => $method,
            'paid_at'           => now(),
        ]);

        $this->recordSettlement($invoice, $gatewayData);
    }

    public static function reference(string $prefix = 'INV'): string
    {
        return strtoupper($prefix) . '-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(6));
    }
}
