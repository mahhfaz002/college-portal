<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Requirements revamp:
 *  - applicants: O'Level results (exam_type, exam_year, olevel_results json)
 *  - programs:   program_type (UG | DIP | CERT) + levels (number of levels)
 *  - subjects:   level (courses are grouped per level)
 *  - rename the ICT role to MIS on existing users
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            if (!Schema::hasColumn('applicants', 'exam_type')) {
                $table->string('exam_type')->nullable();      // WAEC | NECO
            }
            if (!Schema::hasColumn('applicants', 'exam_year')) {
                $table->string('exam_year')->nullable();
            }
            if (!Schema::hasColumn('applicants', 'olevel_results')) {
                $table->json('olevel_results')->nullable();    // [{subject, grade}]
            }
        });

        Schema::table('programs', function (Blueprint $table) {
            if (!Schema::hasColumn('programs', 'program_type')) {
                $table->string('program_type')->default('UG'); // UG | DIP | CERT
            }
            if (!Schema::hasColumn('programs', 'levels')) {
                $table->unsignedInteger('levels')->default(1);
            }
            if (!Schema::hasColumn('programs', 'id_format')) {
                // Student registration-number template for this course of study.
                $table->string('id_format')->nullable();
            }
        });

        Schema::table('subjects', function (Blueprint $table) {
            if (!Schema::hasColumn('subjects', 'level')) {
                $table->string('level')->nullable();
            }
        });

        // Rename the ICT role -> MIS.
        DB::table('users')->where('role', 'ict')->update(['role' => 'mis']);
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'mis')->update(['role' => 'ict']);
    }
};
