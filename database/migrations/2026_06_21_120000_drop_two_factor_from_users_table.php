<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Remove the email-OTP two-factor columns. The 2FA feature was pulled before
 * full launch; it will be redesigned later. Guarded so it is a no-op on any
 * environment where the columns were never created.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['two_factor_code', 'two_factor_expires_at'] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'two_factor_code')) {
                $table->string('two_factor_code')->nullable();
            }
            if (! Schema::hasColumn('users', 'two_factor_expires_at')) {
                $table->timestamp('two_factor_expires_at')->nullable();
            }
        });
    }
};
