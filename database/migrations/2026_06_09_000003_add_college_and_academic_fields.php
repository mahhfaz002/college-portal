<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tenant-scoped tables get a nullable college_id (the CollegeScope filters on it).
     * Nullable so existing rows + console/seeding keep working before assignment.
     */
    private array $tenantTables = [
        'users', 'students', 'applicants', 'subjects', 'payments', 'fee_bills',
        'exams', 'attendances', 'announcements', 'settings', 'scores',
        'employees', 'books', 'inventory_items', 'staff_attendance',
    ];

    public function up(): void
    {
        foreach ($this->tenantTables as $tableName) {
            if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'college_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->unsignedBigInteger('college_id')->nullable()->index();
                });
            }
        }

        // Academic placement on staff.
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'department_id')) {
                $table->unsignedBigInteger('department_id')->nullable()->index();
            }
            if (!Schema::hasColumn('users', 'program_id')) {
                $table->unsignedBigInteger('program_id')->nullable()->index();
            }
            // academic | non_academic | hod | assistant_hod | secretary | ...
            if (!Schema::hasColumn('users', 'staff_category')) {
                $table->string('staff_category')->nullable();
            }
        });

        // Academic placement + registration workflow state on students.
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'department_id')) {
                $table->unsignedBigInteger('department_id')->nullable()->index();
            }
            if (!Schema::hasColumn('students', 'program_id')) {
                $table->unsignedBigInteger('program_id')->nullable()->index();
            }
            if (!Schema::hasColumn('students', 'level')) {
                $table->string('level')->nullable();
            }
            // not_started | pending_hod | registered (HOD approval gate, Phase 3)
            if (!Schema::hasColumn('students', 'registration_status')) {
                $table->string('registration_status')->default('not_started');
            }
        });

        // Course attributes on the (relabelled) subjects table.
        Schema::table('subjects', function (Blueprint $table) {
            if (!Schema::hasColumn('subjects', 'course_code')) {
                $table->string('course_code')->nullable();
            }
            if (!Schema::hasColumn('subjects', 'course_unit')) {
                $table->unsignedTinyInteger('course_unit')->nullable();
            }
            if (!Schema::hasColumn('subjects', 'department_id')) {
                $table->unsignedBigInteger('department_id')->nullable()->index();
            }
            if (!Schema::hasColumn('subjects', 'program_id')) {
                $table->unsignedBigInteger('program_id')->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        foreach ($this->tenantTables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'college_id')) {
                Schema::table($tableName, fn (Blueprint $table) => $table->dropColumn('college_id'));
            }
        }
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['department_id', 'program_id', 'staff_category']));
        Schema::table('students', fn (Blueprint $t) => $t->dropColumn(['department_id', 'program_id', 'level', 'registration_status']));
        Schema::table('subjects', fn (Blueprint $t) => $t->dropColumn(['course_code', 'course_unit', 'department_id', 'program_id']));
    }
};
