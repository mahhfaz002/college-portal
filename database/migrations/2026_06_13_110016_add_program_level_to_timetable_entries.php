<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Timetable rebuilt on the tertiary structure: entries are now keyed by
 * programme + level (the old class_arm column is reused to hold a human label
 * like "ND Science Lab Tech · L100" so existing grid views keep working).
 *
 * No ->after(): MySQL-only positioning is silently skipped on SQLite (dev).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('timetable_entries', function (Blueprint $table) {
            if (! Schema::hasColumn('timetable_entries', 'program_id')) {
                $table->unsignedBigInteger('program_id')->nullable();
            }
            if (! Schema::hasColumn('timetable_entries', 'level')) {
                $table->string('level')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('timetable_entries', function (Blueprint $table) {
            $table->dropColumn(['program_id', 'level']);
        });
    }
};
