<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Paystack Marketplace / Subaccount support.
 *
 *  - colleges  : per-institution settlement details + subaccount link + commission.
 *  - invoices  : settlement split + gateway response captured at verification time
 *                (Invoice stays the single transaction record — no new payment entity).
 *  - paystack_webhook_events : webhook log for idempotency, replay protection & retry.
 *
 * All column adds are guarded with hasColumn (NO ->after(): MySQL-only and silently
 * skipped on SQLite) so this is safe on both the SQLite dev DB and prod MySQL.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('colleges', function (Blueprint $t) {
            if (!Schema::hasColumn('colleges', 'paystack_subaccount_code'))   $t->string('paystack_subaccount_code')->nullable();
            if (!Schema::hasColumn('colleges', 'paystack_subaccount_name'))   $t->string('paystack_subaccount_name')->nullable();
            if (!Schema::hasColumn('colleges', 'commission_percentage'))      $t->decimal('commission_percentage', 5, 2)->default(2.00);
            if (!Schema::hasColumn('colleges', 'settlement_bank'))            $t->string('settlement_bank')->nullable();           // Paystack bank code
            if (!Schema::hasColumn('colleges', 'settlement_account_number'))  $t->string('settlement_account_number')->nullable();
            if (!Schema::hasColumn('colleges', 'settlement_account_name'))    $t->string('settlement_account_name')->nullable();
            if (!Schema::hasColumn('colleges', 'paystack_subaccount_status')) $t->string('paystack_subaccount_status')->default('pending'); // pending|active|inactive
        });

        Schema::table('invoices', function (Blueprint $t) {
            if (!Schema::hasColumn('invoices', 'platform_commission'))  $t->decimal('platform_commission', 12, 2)->nullable();
            if (!Schema::hasColumn('invoices', 'institution_share'))    $t->decimal('institution_share', 12, 2)->nullable();
            if (!Schema::hasColumn('invoices', 'settlement_status'))    $t->string('settlement_status')->nullable();   // pending|settled|failed
            if (!Schema::hasColumn('invoices', 'settlement_reference')) $t->string('settlement_reference')->nullable();
            if (!Schema::hasColumn('invoices', 'settlement_at'))        $t->timestamp('settlement_at')->nullable();
            if (!Schema::hasColumn('invoices', 'gateway_response'))     $t->json('gateway_response')->nullable();
        });

        if (!Schema::hasTable('paystack_webhook_events')) {
            Schema::create('paystack_webhook_events', function (Blueprint $t) {
                $t->id();
                $t->string('event')->index();
                $t->string('reference')->nullable()->index();
                $t->string('dedupe_key')->nullable()->unique();   // replay / duplicate protection
                $t->unsignedBigInteger('college_id')->nullable()->index();
                $t->boolean('signature_valid')->default(false);
                $t->string('status')->default('received');        // received|processed|ignored|failed
                $t->unsignedInteger('attempts')->default(0);
                $t->text('error')->nullable();
                $t->json('payload')->nullable();
                $t->timestamp('processed_at')->nullable();
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::table('colleges', function (Blueprint $t) {
            foreach (['paystack_subaccount_code', 'paystack_subaccount_name', 'commission_percentage',
                      'settlement_bank', 'settlement_account_number', 'settlement_account_name',
                      'paystack_subaccount_status'] as $c) {
                if (Schema::hasColumn('colleges', $c)) $t->dropColumn($c);
            }
        });
        Schema::table('invoices', function (Blueprint $t) {
            foreach (['platform_commission', 'institution_share', 'settlement_status',
                      'settlement_reference', 'settlement_at', 'gateway_response'] as $c) {
                if (Schema::hasColumn('invoices', $c)) $t->dropColumn($c);
            }
        });
        Schema::dropIfExists('paystack_webhook_events');
    }
};
