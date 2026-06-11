<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * - users: lecturer (resource person) academic profile.
 * - exams: a "submitted_at" lock so a lecturer cannot edit once questions are
 *   forwarded to the exam officer.
 * - subjects already carry program_id + level (added earlier).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach ([
                'qualification'   => fn () => $table->string('qualification')->nullable(),
                'university'      => fn () => $table->string('university')->nullable(),
                'class_of_degree' => fn () => $table->string('class_of_degree')->nullable(),
                'address'         => fn () => $table->string('address')->nullable(),
            ] as $col => $def) {
                if (!Schema::hasColumn('users', $col)) {
                    $def();
                }
            }
        });

        Schema::table('exams', function (Blueprint $table) {
            if (!Schema::hasColumn('exams', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable(); // locked to exam officer when set
            }
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            if (Schema::hasColumn('exams', 'submitted_at')) {
                $table->dropColumn('submitted_at');
            }
        });
    }
};
