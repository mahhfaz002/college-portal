<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Courses are created per semester (First / Second / Summer). The Academic
 * Secretary picks the semester in Create Courses; HODs filter by it.
 *
 * No ->after(): MySQL-only positioning is silently skipped on SQLite (dev).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            if (! Schema::hasColumn('subjects', 'semester')) {
                $table->string('semester')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropColumn('semester');
        });
    }
};
