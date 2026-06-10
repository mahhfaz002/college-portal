<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 2 — online application. Adds applicant bio (Section A), guardian
 * (Section B), sponsor (Section C), program choices, the linked user account
 * and the application/admission workflow state.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            $add = function (string $col, callable $def) use ($table) {
                if (!Schema::hasColumn('applicants', $col)) {
                    $def();
                }
            };

            $add('college_id', fn () => $table->unsignedBigInteger('college_id')->nullable()->index());
            $add('user_id', fn () => $table->unsignedBigInteger('user_id')->nullable()->index());

            // Section A — applicant bio
            $add('first_name', fn () => $table->string('first_name')->nullable());
            $add('surname', fn () => $table->string('surname')->nullable());
            $add('other_name', fn () => $table->string('other_name')->nullable());
            $add('phone', fn () => $table->string('phone')->nullable());
            $add('email', fn () => $table->string('email')->nullable());

            // Program choices
            $add('first_choice_program_id', fn () => $table->unsignedBigInteger('first_choice_program_id')->nullable()->index());
            $add('second_choice_program_id', fn () => $table->unsignedBigInteger('second_choice_program_id')->nullable()->index());

            // Section B — parent / guardian
            $add('guardian_name', fn () => $table->string('guardian_name')->nullable());
            $add('guardian_relationship', fn () => $table->string('guardian_relationship')->nullable());
            $add('guardian_phone', fn () => $table->string('guardian_phone')->nullable());
            $add('guardian_email', fn () => $table->string('guardian_email')->nullable());
            $add('guardian_address', fn () => $table->string('guardian_address')->nullable());
            $add('guardian_occupation', fn () => $table->string('guardian_occupation')->nullable());

            // Section C — sponsor
            $add('sponsor_name', fn () => $table->string('sponsor_name')->nullable());
            $add('sponsor_relationship', fn () => $table->string('sponsor_relationship')->nullable());
            $add('sponsor_phone', fn () => $table->string('sponsor_phone')->nullable());
            $add('sponsor_address', fn () => $table->string('sponsor_address')->nullable());

            // Workflow
            // application_status: pending_payment | submitted | admitted | rejected
            $add('application_status', fn () => $table->string('application_status')->default('pending_payment'));
            $add('payment_status', fn () => $table->string('payment_status')->default('unpaid'));
            // Admission decision (Phase 3): the program the candidate is offered.
            $add('admitted_program_id', fn () => $table->unsignedBigInteger('admitted_program_id')->nullable());
            $add('admission_response', fn () => $table->string('admission_response')->nullable()); // accepted | rejected
        });
    }

    public function down(): void
    {
        // Non-destructive: leave columns in place on rollback.
    }
};
