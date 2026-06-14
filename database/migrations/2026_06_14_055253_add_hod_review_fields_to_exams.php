<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * HOD review of submitted question sets: approve, or query back to the lecturer
 * with reasons / question numbers / recommendation.
 * No ->after() (MySQL-only; SQLite skips it silently).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            if (! Schema::hasColumn('exams', 'hod_feedback')) {
                $table->text('hod_feedback')->nullable();
            }
            if (! Schema::hasColumn('exams', 'reviewed_by')) {
                $table->unsignedBigInteger('reviewed_by')->nullable();
            }
            if (! Schema::hasColumn('exams', 'reviewed_at')) {
                $table->dateTime('reviewed_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('exams', fn (Blueprint $t) => $t->dropColumn(['hod_feedback', 'reviewed_by', 'reviewed_at']));
    }
};
