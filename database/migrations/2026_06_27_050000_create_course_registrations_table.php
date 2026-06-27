<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('college_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->string('term');
            $table->string('session');
            $table->boolean('is_carryover')->default(false);
            $table->timestamp('registered_at')->nullable();
            $table->timestamp('dropped_at')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'subject_id', 'term', 'session'], 'course_reg_unique');
        });

        Schema::table('programs', function (Blueprint $table) {
            if (!Schema::hasColumn('programs', 'max_credit_units')) {
                $table->unsignedInteger('max_credit_units')->nullable()->default(24);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_registrations');

        Schema::table('programs', function (Blueprint $table) {
            if (Schema::hasColumn('programs', 'max_credit_units')) {
                $table->dropColumn('max_credit_units');
            }
        });
    }
};
