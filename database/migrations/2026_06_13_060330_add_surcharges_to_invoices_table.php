<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Online-payment surcharges, itemised on the checkout page and the receipt:
 *  - convenience_fee: flat portal fee charged on every online transaction.
 *  - service_fee:     Paystack's processing fee, passed on to the payer.
 * The amount charged at the gateway = amount + convenience_fee + service_fee.
 *
 * No ->after(): column positioning is MySQL-only and is silently skipped on
 * SQLite (dev), which would leave the columns unadded.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('invoices', 'convenience_fee')) {
                $table->decimal('convenience_fee', 12, 2)->default(0);
            }
            if (! Schema::hasColumn('invoices', 'service_fee')) {
                $table->decimal('service_fee', 12, 2)->default(0);
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['convenience_fee', 'service_fee']);
        });
    }
};
