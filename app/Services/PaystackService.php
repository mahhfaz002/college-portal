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

        $bank    = $details['settlement_bank'] ?? $college->settlement_bank;
        $account = $details['account_number']  ?? $college->settlement_account_number;

        $payload = [
            'business_name'         => $details['business_name'] ?? $college->name,
            'settlement_bank'       => $bank,
            'account_number'        => $account,
            'percentage_charge'     => (float) ($details['percentage_charge']
                                        ?? $college->commission_percentage
                                        ?? config('services.paystack.default_commission_percentage', 2)),
            'primary_contact_email' => $college->email ?: config('services.paystack.fallback_email'),
        ];

        // SELF-HEAL: if we have no stored code but Paystack already has a subaccount
        // for this exact settlement account, ADOPT it (PUT update) instead of POSTing
        // a duplicate. This is what makes re-clicking "Create" idempotent and lets a
        // code that exists on Paystack but was never saved locally be reconciled.
        if (empty($college->paystack_subaccount_code) && $bank && $account) {
            $existing = $this->findSubaccountByAccount($bank, $account);
            if ($existing && !empty($existing['subaccount_code'])) {
                $college->forceFill(['paystack_subaccount_code' => $existing['subaccount_code']])->save();
            }
        }

        $http = Http::withToken($secret)->acceptJson();
        $resp = $college->paystack_subaccount_code
            ? $http->put($this->endpoint('/subaccount/' . $college->paystack_subaccount_code), $payload)
            : $http->post($this->endpoint('/subaccount'), $payload);

        if (!$resp->ok() || !$resp->json('status')) {
            \Log::warning('Paystack subaccount failed', ['college' => $college->id, 'body' => $resp->json()]);
            throw new \RuntimeException('Paystack subaccount error: ' . $resp->json('message', 'request failed'));
        }

        $data = $resp->json('data');
        $code = $data['subaccount_code'] ?? $college->paystack_subaccount_code;

        // Persist the code FIRST and on its own, so a hiccup writing the cosmetic
        // fields can never lose the one value the whole split depends on.
        if ($code && $code !== $college->paystack_subaccount_code) {
            $college->forceFill(['paystack_subaccount_code' => $code])->save();
        }
        $college->forceFill([
            'paystack_subaccount_code'   => $code,
            'paystack_subaccount_name'   => $data['business_name'] ?? $payload['business_name'],
            'settlement_account_name'    => $data['account_name'] ?? $college->settlement_account_name,
            'paystack_subaccount_status' => (($data['active'] ?? true)) ? 'active' : 'inactive',
        ])->save();

        if (empty($college->fresh()->paystack_subaccount_code)) {
            throw new \RuntimeException('Subaccount was created on Paystack but its code could not be stored. Please retry.');
        }

        return $data;
    }

    /**
     * One page (or all pages) of the platform's Paystack subaccounts. Used to
     * reconcile a college whose subaccount exists on Paystack but isn't linked
     * locally (lost code, created manually, or created by an older version).
     *
     * @return array<int, array<string, mixed>>
     */
    public function listSubaccounts(int $perPage = 200): array
    {
        if (empty($this->masterSecret())) {
            return [];
        }

        $all  = [];
        $page = 1;
        do {
            $resp = Http::withToken($this->masterSecret())->acceptJson()
                ->get($this->endpoint('/subaccount'), ['perPage' => $perPage, 'page' => $page]);
            if (!$resp->ok() || !$resp->json('status')) {
                \Log::warning('Paystack list subaccounts failed', [
                    'page' => $page, 'http' => $resp->status(), 'message' => $resp->json('message'),
                ]);
                break;
            }
            $batch = $resp->json('data') ?? [];
            $all   = array_merge($all, $batch);
            $page++;
        } while (count($batch) === $perPage && $page <= 25); // hard stop: never loop forever

        return $all;
    }

    /**
     * Find an existing Paystack subaccount that settles to the given account
     * number. Returns the raw subaccount record (incl. subaccount_code) or null.
     * The backbone of subaccount reconciliation / recovery.
     *
     * Matching is keyed on the ACCOUNT NUMBER, which uniquely identifies an
     * institution's settlement account within the platform's Paystack business.
     * The bank is only a tie-breaker for the (vanishingly rare) case of the same
     * account number at two banks — and even then it tolerates Paystack returning
     * `settlement_bank` as the bank NAME (e.g. "Kuda Bank") while we store the
     * bank CODE (e.g. "50211"). Requiring code === name was THE bug that made
     * "Recover from Paystack" silently find nothing.
     */
    public function findSubaccountByAccount(string $bankCode, string $accountNumber): ?array
    {
        $account = $this->normalizeAccount($accountNumber);
        if ($account === '') {
            return null;
        }

        $matches = array_values(array_filter(
            $this->listSubaccounts(),
            fn ($sub) => $this->normalizeAccount((string) ($sub['account_number'] ?? '')) === $account
        ));

        if (count($matches) <= 1) {
            return $matches[0] ?? null;   // account number is a unique enough key
        }

        // Same account number on multiple subaccounts → disambiguate by bank,
        // accepting either the bank CODE or the resolved bank NAME.
        $wantCode = strtolower(trim($bankCode));
        $wantName = strtolower((string) $this->bankNameForCode($bankCode));
        foreach ($matches as $sub) {
            $sb = strtolower(trim((string) ($sub['settlement_bank'] ?? $sub['bank'] ?? '')));
            if ($sb !== '' && ($sb === $wantCode || ($wantName !== '' && $sb === $wantName))) {
                return $sub;
            }
        }

        return $matches[0];
    }

    /** Normalise an account number for comparison (trim + drop leading zeros). */
    private function normalizeAccount(string $n): string
    {
        return ltrim(trim($n), '0');
    }

    /** Resolve a Paystack bank code to its display name via the banks list. */
    private function bankNameForCode(?string $code): ?string
    {
        if (!$code) {
            return null;
        }
        foreach ($this->banks() as $b) {
            if ((string) ($b['code'] ?? '') === (string) $code) {
                return $b['name'] ?? null;
            }
        }
        return null;
    }

    /**
     * Ensure a college has a usable subaccount: adopt an existing one by account
     * number, or create it. Idempotent. Returns the subaccount code (or null if
     * the college has no settlement account to work with). Used by the reconcile
     * command and as the last-resort self-heal at payment time.
     */
    public function ensureSubaccount(College $college): ?string
    {
        if ($college->usesSubaccount()) {
            return $college->paystack_subaccount_code;
        }
        if (empty($college->settlement_account_number) || empty($college->settlement_bank)) {
            return null; // nothing to link against yet
        }

        $this->createOrUpdateSubaccount($college, [
            'settlement_bank'   => $college->settlement_bank,
            'account_number'    => $college->settlement_account_number,
            'percentage_charge' => $college->commission_percentage,
            'business_name'     => $college->name,
        ]);

        return $college->fresh()->paystack_subaccount_code;
    }

    /** Refresh a college's subaccount details/status from Paystack. */
    public function fetchSubaccount(College $college): ?array
    {
        if (empty($this->masterSecret())) {
            return null;
        }

        // RECOVERY: no local code but settlement details on file → look the
        // subaccount up by account number and adopt its code, so a code that
        // exists on Paystack (lost locally / made manually) is pulled back in.
        if (!$college->paystack_subaccount_code
            && $college->settlement_bank && $college->settlement_account_number) {
            $existing = $this->findSubaccountByAccount($college->settlement_bank, $college->settlement_account_number);
            if ($existing && !empty($existing['subaccount_code'])) {
                $college->forceFill([
                    'paystack_subaccount_code'   => $existing['subaccount_code'],
                    'paystack_subaccount_name'   => $existing['business_name'] ?? $college->paystack_subaccount_name,
                    'paystack_subaccount_status' => (($existing['active'] ?? true)) ? 'active' : 'inactive',
                ])->save();
            }
        }

        if (!$college->paystack_subaccount_code) {
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
        // Guard against a database that hasn't run the subaccount migration yet
        // (e.g. a deploy that skipped `migrate --force`) so marking an invoice
        // paid can never 500 over a missing settlement column.
        if (!\Illuminate\Support\Facades\Schema::hasColumn('invoices', 'institution_share')) {
            return;
        }
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

        // Mint a FRESH reference for THIS attempt. Paystack rejects a reference it
        // has already seen, so reusing the stored one breaks every retry. The new
        // reference keeps the fixed college tag and a unique changing tail, and is
        // persisted so verify()/callback/webhook all match this attempt.
        $invoice->forceFill([
            'reference' => self::reference($this->prefixForInvoice($invoice), $invoice->college_id),
        ])->save();

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
        if (!$this->isPlatformInvoice($invoice) && $college) {
            // LAST-RESORT SELF-HEAL: a college with settlement details but no linked
            // subaccount would otherwise be charged WITHOUT a split (the whole bug).
            // Try to reconcile the code right here so the split still happens.
            if (!$college->usesSubaccount()
                && $college->settlement_bank && $college->settlement_account_number) {
                try {
                    $this->ensureSubaccount($college);
                    $college->refresh();
                } catch (\Throwable $e) {
                    \Log::warning('Paystack split self-heal failed at initialize', [
                        'college' => $college->id, 'invoice' => $invoice->id, 'error' => $e->getMessage(),
                    ]);
                }
            }

            if ($college->usesSubaccount()) {
                $payload['subaccount'] = $college->paystack_subaccount_code;
                $payload['bearer']     = config('services.paystack.subaccount_bearer', 'account');
            } elseif ($college->settlement_account_number) {
                // Settlement account set up but still no subaccount → the payment
                // will NOT split. Make that loud instead of silently going to master.
                \Log::warning('Paystack payment will NOT split — college has settlement account but no subaccount code', [
                    'college' => $college->id, 'invoice' => $invoice->id,
                ]);
            }
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

        // Void any DUPLICATE pending invoices for the same payer + purpose (e.g. a
        // re-issued acceptance fee), so the dashboard never shows a stray "Pay Now"
        // for a fee that's already settled.
        Invoice::withoutGlobalScopes()
            ->where('id', '!=', $invoice->id)
            ->where('purpose', $invoice->purpose)
            ->where('status', 'pending')
            ->when($invoice->applicant_id, fn ($q) => $q->where('applicant_id', $invoice->applicant_id))
            ->when(!$invoice->applicant_id && $invoice->student_id, fn ($q) => $q->where('student_id', $invoice->student_id))
            ->when(!$invoice->applicant_id && !$invoice->student_id && $invoice->user_id, fn ($q) => $q->where('user_id', $invoice->user_id))
            ->update(['status' => 'cancelled']);

        // Audit trail for money received (webhook/callback may have no auth user;
        // stamp the invoice's college explicitly so it stays tenant-scoped).
        \App\Models\ActivityLog::create([
            'college_id'  => $invoice->college_id,
            'user_id'     => auth()->id(),
            'action'      => 'payment.paid',
            'description' => 'Payment received — '.$invoice->purpose.' '.money((float) $invoice->amount)
                             .' (invoice #'.$invoice->id.', ref '.$invoice->reference.', via '.$method.')',
        ]);

        $this->recordSettlement($invoice, $gatewayData);
    }

    /**
     * A globally-unique transaction reference of the form
     *   PREFIX-C{collegeId}-{YmdHis+microseconds}{random}
     * The fixed `C{collegeId}` tag identifies the owning college; the changing
     * tail guarantees every attempt (even a retry of the same invoice) is unique,
     * so Paystack never rejects a duplicate reference.
     */
    public static function reference(string $prefix = 'INV', ?int $collegeId = null): string
    {
        $cid = $collegeId ?? (current_college_id() ?? 0);

        do {
            $tail = now()->format('YmdHisu') . strtoupper(Str::random(4));
            $ref  = strtoupper($prefix) . '-C' . $cid . '-' . $tail;
        } while (Invoice::withoutGlobalScopes()->where('reference', $ref)->exists());

        return $ref;
    }

    /** Short reference prefix derived from the invoice purpose. */
    private function prefixForInvoice(Invoice $invoice): string
    {
        return match ($invoice->purpose) {
            'application_fee'       => 'APP',
            'acceptance_fee'        => 'ACC',
            'registration_fee'      => 'REG',
            'platform_registration' => 'PLT',
            'change_of_course'      => 'COC',
            default                 => 'INV',
        };
    }
}
