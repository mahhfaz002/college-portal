<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Expand the change-of-course request into a full multi-stage approval chain:
 *   secretary_review → new_hod_review → (new HOD decision) → current_hod_review
 *   → (current HOD decision) → registrar_review → approved/rejected, then the
 *   student pays the new-course registration fee and is migrated.
 *
 * Each reviewer leaves a comment; the Academic Secretary forwards/relays.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('change_of_course_requests', function (Blueprint $table) {
            $add = function (string $name, callable $def) use ($table) {
                if (! Schema::hasColumn('change_of_course_requests', $name)) {
                    $def($table);
                }
            };

            // Academic Secretary's own comment (kept separate from the legacy note).
            $add('secretary_comment', fn ($t) => $t->text('secretary_comment')->nullable());

            // New-department HOD decision.
            $add('new_hod_id', fn ($t) => $t->unsignedBigInteger('new_hod_id')->nullable());
            $add('new_hod_decision', fn ($t) => $t->string('new_hod_decision')->nullable()); // accept|reject
            $add('new_hod_comment', fn ($t) => $t->text('new_hod_comment')->nullable());
            $add('new_hod_at', fn ($t) => $t->timestamp('new_hod_at')->nullable());

            // Current-department HOD decision.
            $add('current_hod_id', fn ($t) => $t->unsignedBigInteger('current_hod_id')->nullable());
            $add('current_hod_decision', fn ($t) => $t->string('current_hod_decision')->nullable());
            $add('current_hod_comment', fn ($t) => $t->text('current_hod_comment')->nullable());
            $add('current_hod_at', fn ($t) => $t->timestamp('current_hod_at')->nullable());

            // Registrar approval note (registrar_reason already holds a rejection reason).
            $add('registrar_comment', fn ($t) => $t->text('registrar_comment')->nullable());

            // Final reason shown to the student + which stage produced the rejection.
            $add('rejection_reason', fn ($t) => $t->text('rejection_reason')->nullable());
            $add('rejected_stage', fn ($t) => $t->string('rejected_stage')->nullable());

            // Forward timestamps (audit / display).
            $add('forwarded_to_new_hod_at', fn ($t) => $t->timestamp('forwarded_to_new_hod_at')->nullable());
            $add('forwarded_to_current_hod_at', fn ($t) => $t->timestamp('forwarded_to_current_hod_at')->nullable());
            $add('forwarded_to_registrar_at', fn ($t) => $t->timestamp('forwarded_to_registrar_at')->nullable());

            // Post-approval: new-course registration fee + migration.
            $add('new_registration_invoice_id', fn ($t) => $t->unsignedBigInteger('new_registration_invoice_id')->nullable());
            $add('new_fee_paid_at', fn ($t) => $t->timestamp('new_fee_paid_at')->nullable());
            $add('migrated_at', fn ($t) => $t->timestamp('migrated_at')->nullable());
        });
    }

    public function down(): void
    {
        Schema::table('change_of_course_requests', function (Blueprint $table) {
            foreach ([
                'secretary_comment',
                'new_hod_id', 'new_hod_decision', 'new_hod_comment', 'new_hod_at',
                'current_hod_id', 'current_hod_decision', 'current_hod_comment', 'current_hod_at',
                'registrar_comment', 'rejection_reason', 'rejected_stage',
                'forwarded_to_new_hod_at', 'forwarded_to_current_hod_at', 'forwarded_to_registrar_at',
                'new_registration_invoice_id', 'new_fee_paid_at', 'migrated_at',
            ] as $col) {
                if (Schema::hasColumn('change_of_course_requests', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
