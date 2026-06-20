<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Change-of-course applications. A registered student requests a move to another
 * course of study, pays a non-refundable application fee, then the request is
 * reviewed by the Academic Secretary (recommend) and finally decided by the
 * Registrar (approve/reject). Status flow:
 *   pending_payment → under_review → (recommended|not_recommended) → (approved|rejected)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('change_of_course_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('college_id')->nullable()->index();
            $table->unsignedBigInteger('student_id')->index();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('current_program_id')->nullable();
            $table->unsignedBigInteger('requested_program_id');
            $table->text('reason');
            $table->unsignedBigInteger('invoice_id')->nullable();
            $table->string('status')->default('pending_payment');
            // Academic Secretary recommendation
            $table->unsignedBigInteger('secretary_id')->nullable();
            $table->text('secretary_note')->nullable();
            $table->timestamp('recommended_at')->nullable();
            // Registrar final decision
            $table->unsignedBigInteger('registrar_id')->nullable();
            $table->text('registrar_reason')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('change_of_course_requests');
    }
};
