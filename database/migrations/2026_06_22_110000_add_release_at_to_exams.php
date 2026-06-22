<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * When the HOD approves a question set they may schedule WHEN it becomes
 * available to the Exam Officer (e.g. the day before the exam) so the questions
 * can't leak early. Null = available immediately on approval.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $t) {
            if (!Schema::hasColumn('exams', 'release_at')) {
                $t->timestamp('release_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $t) {
            if (Schema::hasColumn('exams', 'release_at')) {
                $t->dropColumn('release_at');
            }
        });
    }
};
