<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reusable online-payment invoice (Paystack). Covers the application fee
 * (Phase 2) and later the acceptance / registration / general fees (Phase 3-4).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('college_id')->nullable()->index();
            $table->unsignedBigInteger('applicant_id')->nullable()->index();
            $table->unsignedBigInteger('student_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('program_id')->nullable();

            // application_fee | acceptance_fee | registration_fee | other
            $table->string('purpose')->default('other');
            $table->string('description');
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('currency', 8)->default('NGN');

            // pending | paid | failed
            $table->string('status')->default('pending');
            $table->string('reference')->unique();         // our reference
            $table->string('gateway_reference')->nullable(); // Paystack reference
            $table->string('payment_method')->nullable();    // paystack | manual | sandbox
            $table->string('payer_email')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
