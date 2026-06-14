<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Exam authoring rework: tie a question set to an exam cycle, and let a
 * question be an objective (options + correct) or a theory question (numbered
 * 1–10, text only). No ->after() (MySQL-only; SQLite skips it silently).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            if (! Schema::hasColumn('exams', 'exam_cycle_id')) {
                $table->unsignedBigInteger('exam_cycle_id')->nullable()->index();
            }
        });

        Schema::table('exam_questions', function (Blueprint $table) {
            if (! Schema::hasColumn('exam_questions', 'type')) {
                $table->string('type')->default('objective');   // objective | theory
            }
            if (! Schema::hasColumn('exam_questions', 'theory_number')) {
                $table->unsignedInteger('theory_number')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('exams', fn (Blueprint $t) => $t->dropColumn('exam_cycle_id'));
        Schema::table('exam_questions', fn (Blueprint $t) => $t->dropColumn(['type', 'theory_number']));
    }
};
