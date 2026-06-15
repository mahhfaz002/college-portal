<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Audit + idempotency log for every Paystack webhook received. The unique
 * `dedupe_key` gives replay / duplicate-delivery protection; `status` tracks
 * processing so failures can be retried.
 *
 * NOTE: not college-scoped — webhooks arrive on the platform master account,
 * before any tenant context is resolved.
 */
class PaystackWebhookEvent extends Model
{
    protected $fillable = [
        'event', 'reference', 'dedupe_key', 'college_id',
        'signature_valid', 'status', 'attempts', 'error', 'payload', 'processed_at',
    ];

    protected $casts = [
        'signature_valid' => 'boolean',
        'payload'         => 'array',
        'processed_at'    => 'datetime',
    ];

    public function markProcessed(?string $note = null): void
    {
        $this->update(['status' => 'processed', 'processed_at' => now(), 'error' => $note]);
    }

    public function markFailed(string $error): void
    {
        $this->update(['status' => 'failed', 'error' => $error]);
    }
}
