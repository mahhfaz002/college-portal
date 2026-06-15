<?php

namespace App\Console\Commands;

use App\Http\Controllers\GatewayPaymentController;
use App\Models\Invoice;
use App\Models\PaystackWebhookEvent;
use Illuminate\Console\Command;

/**
 * Re-process Paystack webhook events that were received with a VALID signature
 * but failed during processing (e.g. a transient DB error). Schedule this to
 * run periodically, or run on demand. Idempotent — handlers re-check state.
 *
 *   php artisan paystack:retry-webhooks
 */
class RetryPaystackWebhooks extends Command
{
    protected $signature = 'paystack:retry-webhooks {--limit=50}';
    protected $description = 'Re-process Paystack webhook events that previously failed processing.';

    public function handle(GatewayPaymentController $controller): int
    {
        $events = PaystackWebhookEvent::where('status', 'failed')
            ->where('signature_valid', true)
            ->latest()->limit((int) $this->option('limit'))->get();

        foreach ($events as $log) {
            try {
                $data    = $log->payload['data'] ?? [];
                $ref     = $data['reference'] ?? $log->reference;
                $invoice = $ref
                    ? Invoice::withoutGlobalScopes()->where('reference', $ref)->orWhere('gateway_reference', $ref)->first()
                    : null;

                $controller->processWebhookEvent($log->event, $data, $invoice);
                $log->markProcessed('retried');
                $this->info("Reprocessed {$log->event} #{$log->id}");
            } catch (\Throwable $e) {
                $log->update(['attempts' => $log->attempts + 1, 'error' => $e->getMessage()]);
                $this->error("Retry failed for #{$log->id}: {$e->getMessage()}");
            }
        }

        $this->info($events->count().' failed event(s) processed.');
        return self::SUCCESS;
    }
}
