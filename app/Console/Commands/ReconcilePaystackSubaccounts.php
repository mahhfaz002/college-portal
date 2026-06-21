<?php

namespace App\Console\Commands;

use App\Models\College;
use App\Services\PaystackService;
use Illuminate\Console\Command;

/**
 * Repair the marketplace split for every college that has settlement details
 * but no linked Paystack subaccount code — the state in which payments go 100%
 * to the master account with no split. For each such college this ADOPTS an
 * existing Paystack subaccount (matched by settlement account number) or creates
 * one, then stores the code so future payments split correctly. Idempotent.
 *
 *   php artisan paystack:reconcile-subaccounts          # link/create as needed
 *   php artisan paystack:reconcile-subaccounts --dry-run
 */
class ReconcilePaystackSubaccounts extends Command
{
    protected $signature = 'paystack:reconcile-subaccounts {--dry-run : Report what would change without calling Paystack}';
    protected $description = 'Link or create the Paystack subaccount for every college with settlement details so payments split.';

    public function handle(PaystackService $paystack): int
    {
        $colleges = College::withoutGlobalScopes()
            ->whereNotNull('settlement_account_number')
            ->whereNotNull('settlement_bank')
            ->orderBy('name')->get();

        if ($colleges->isEmpty()) {
            $this->info('No colleges with settlement details to reconcile.');
            return self::SUCCESS;
        }

        $linked = 0; $already = 0; $failed = 0;
        foreach ($colleges as $college) {
            if ($college->usesSubaccount()) {
                $already++;
                $this->line("• {$college->name}: already linked ({$college->paystack_subaccount_code})");
                continue;
            }

            if ($this->option('dry-run')) {
                $this->warn("• {$college->name}: NO subaccount code — would link/create (acct {$college->settlement_account_number})");
                continue;
            }

            try {
                $code = $paystack->ensureSubaccount($college);
                if ($code) {
                    $linked++;
                    $this->info("✓ {$college->name}: linked subaccount {$code}");
                } else {
                    $failed++;
                    $this->error("✗ {$college->name}: could not link (no usable settlement account)");
                }
            } catch (\Throwable $e) {
                $failed++;
                $this->error("✗ {$college->name}: {$e->getMessage()}");
            }
        }

        $this->newLine();
        $this->info("Done — {$linked} linked, {$already} already linked, {$failed} failed.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
