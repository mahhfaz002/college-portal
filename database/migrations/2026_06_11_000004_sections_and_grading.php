<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * - departments.section: UG | DIP | CERT (a department is placed in a section).
 * - grading_schemes: per-department score→grade bands set by the HOD and applied
 *   to every student in the department.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            if (!Schema::hasColumn('departments', 'section')) {
                $table->string('section')->nullable()->index(); // UG | DIP | CERT
            }
        });

        // Backfill department.section from its programs' program_type.
        if (Schema::hasColumn('programs', 'program_type')) {
            foreach (DB::table('departments')->whereNull('section')->get() as $dept) {
                $type = DB::table('programs')->where('department_id', $dept->id)->value('program_type');
                if ($type) {
                    DB::table('departments')->where('id', $dept->id)->update(['section' => $type]);
                }
            }
        }

        if (!Schema::hasTable('grading_schemes')) {
            Schema::create('grading_schemes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('college_id')->nullable()->index();
                $table->unsignedBigInteger('department_id')->index();
                $table->string('grade');          // e.g. A, B, C ... or A1, B2
                $table->unsignedSmallInteger('min_score');
                $table->unsignedSmallInteger('max_score');
                $table->string('remark')->nullable();
                $table->unsignedTinyInteger('sort')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('grading_schemes');
    }
};
