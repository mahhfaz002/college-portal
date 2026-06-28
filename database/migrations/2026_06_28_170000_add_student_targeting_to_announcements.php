<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lets a student-targeted announcement be narrowed to a department, a course of
 * study (programme) or a level — used by Student Affairs, whose announcements
 * only ever go to students.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            if (! Schema::hasColumn('announcements', 'target_department_id')) {
                $table->unsignedBigInteger('target_department_id')->nullable();
            }
            if (! Schema::hasColumn('announcements', 'target_program_id')) {
                $table->unsignedBigInteger('target_program_id')->nullable();
            }
            if (! Schema::hasColumn('announcements', 'target_level')) {
                $table->string('target_level', 20)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            foreach (['target_department_id', 'target_program_id', 'target_level'] as $col) {
                if (Schema::hasColumn('announcements', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
