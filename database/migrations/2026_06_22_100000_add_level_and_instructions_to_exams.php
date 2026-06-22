<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Exams now target a college LEVEL (+ term/session) rather than a K-12 "class",
 * and the lecturer may add OPTIONAL instructions for each section (objective /
 * theory) that print on the question paper.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $t) {
            if (!Schema::hasColumn('exams', 'level')) {
                $t->string('level')->nullable();
            }
            if (!Schema::hasColumn('exams', 'instructions_objective')) {
                $t->text('instructions_objective')->nullable();
            }
            if (!Schema::hasColumn('exams', 'instructions_theory')) {
                $t->text('instructions_theory')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $t) {
            foreach (['level', 'instructions_objective', 'instructions_theory'] as $c) {
                if (Schema::hasColumn('exams', $c)) {
                    $t->dropColumn($c);
                }
            }
        });
    }
};
