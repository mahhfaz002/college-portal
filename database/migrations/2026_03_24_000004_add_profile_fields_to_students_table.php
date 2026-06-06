<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'email')) {
                $table->string('email')->nullable()->after('full_name');
            }
            if (!Schema::hasColumn('students', 'blood_group')) {
                $table->string('blood_group')->nullable();
            }
            if (!Schema::hasColumn('students', 'photo')) {
                $table->string('photo')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['email', 'blood_group', 'photo']);
        });
    }
};
