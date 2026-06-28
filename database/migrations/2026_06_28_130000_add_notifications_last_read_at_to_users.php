<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tracks when a user last opened their notifications, so the bell badge can
 * show only NEW items and clear to zero once the page is opened.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'notifications_last_read_at')) {
                $table->timestamp('notifications_last_read_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'notifications_last_read_at')) {
                $table->dropColumn('notifications_last_read_at');
            }
        });
    }
};
