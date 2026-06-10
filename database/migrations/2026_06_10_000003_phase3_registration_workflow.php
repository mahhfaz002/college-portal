<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 3 — admission → acceptance → registration → HOD approval.
 *  - students.registration_number (the StudentIdGenerator ID)
 *  - student_documents: uploaded registration documents reviewed by the HOD
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'registration_number')) {
                $table->string('registration_number')->nullable()->after('admission_number');
            }
            if (!Schema::hasColumn('students', 'applicant_id')) {
                $table->unsignedBigInteger('applicant_id')->nullable()->index();
            }
        });

        if (!Schema::hasTable('student_documents')) {
            Schema::create('student_documents', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('college_id')->nullable()->index();
                $table->unsignedBigInteger('student_id')->nullable()->index();
                $table->unsignedBigInteger('applicant_id')->nullable()->index();
                $table->string('type');            // passport, admission_letter, fslc, ssce, diploma, indigene, birth_cert, acceptance_form, receipt, other
                $table->string('label')->nullable();
                $table->string('path');
                $table->string('original_name')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('student_documents');
    }
};
