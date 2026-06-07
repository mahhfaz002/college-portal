<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'first_name')) {
                $table->string('first_name')->nullable();
            }
            if (!Schema::hasColumn('users', 'surname')) {
                $table->string('surname')->nullable();
            }
            if (!Schema::hasColumn('users', 'staff_id')) {
                $table->string('staff_id')->nullable()->unique();
            }
            if (!Schema::hasColumn('users', 'phone')) {
                $table->string('phone')->nullable();
            }
            if (!Schema::hasColumn('users', 'passport')) {
                $table->longText('passport')->nullable(); // base64 data URI
            }
            if (!Schema::hasColumn('users', 'department')) {
                $table->string('department')->nullable(); // optional (colleges/universities)
            }
            if (!Schema::hasColumn('users', 'employed_year')) {
                $table->string('employed_year')->nullable();
            }
            if (!Schema::hasColumn('users', 'next_of_kin_name')) {
                $table->string('next_of_kin_name')->nullable();
            }
            if (!Schema::hasColumn('users', 'next_of_kin_phone')) {
                $table->string('next_of_kin_phone')->nullable();
            }
            if (!Schema::hasColumn('users', 'status')) {
                $table->string('status')->default('active'); // active|inactive
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach ([
                'first_name', 'surname', 'staff_id', 'phone', 'passport',
                'department', 'employed_year', 'next_of_kin_name',
                'next_of_kin_phone', 'status',
            ] as $col) {
                if (Schema::hasColumn('users', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
