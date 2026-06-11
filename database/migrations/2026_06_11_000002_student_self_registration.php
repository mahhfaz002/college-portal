<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Student self-onboarding: auto-generated locked username + a flag that gates
 * access until the one-off platform registration fee is paid.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'username')) {
                $table->string('username')->nullable()->index();
            }
            if (!Schema::hasColumn('users', 'platform_fee_paid')) {
                $table->boolean('platform_fee_paid')->default(true); // staff default paid/exempt
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['username', 'platform_fee_paid'] as $c) {
                if (Schema::hasColumn('users', $c)) {
                    $table->dropColumn($c);
                }
            }
        });
    }
};
