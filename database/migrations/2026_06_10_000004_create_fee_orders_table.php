<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4 — Bursar payment orders. A fee order is a single instruction
 * ("2026 Tuition", ₦75,000) targeted at a scope of students; it fans out to one
 * Invoice per student (settled online via Paystack, reusing Phase 2 machinery).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('college_id')->nullable()->index();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('title');
            $table->string('description')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            // all | department | program | level | students
            $table->string('scope_type')->default('all');
            $table->string('scope_label')->nullable();   // human-readable target
            $table->unsignedInteger('students_count')->default(0);
            $table->timestamps();
        });

        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'fee_order_id')) {
                $table->unsignedBigInteger('fee_order_id')->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_orders');
    }
};
