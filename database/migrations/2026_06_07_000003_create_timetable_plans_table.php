<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('timetable_plans')) {
            Schema::create('timetable_plans', function (Blueprint $table) {
                $table->id();
                $table->string('term')->nullable();
                $table->string('session')->nullable();
                $table->string('status')->default('draft'); // draft | approved
                $table->json('params')->nullable();
                $table->string('engine')->nullable();        // ai | fallback
                $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->dateTime('approved_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('timetable_entries')) {
            Schema::create('timetable_entries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('plan_id')->constrained('timetable_plans')->onDelete('cascade');
                $table->string('class_arm');
                $table->string('day');           // Monday..Friday
                $table->unsignedInteger('period_no');
                $table->string('start_time');    // HH:MM
                $table->string('end_time');      // HH:MM
                $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
                $table->foreignId('teacher_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('timetable_entries');
        Schema::dropIfExists('timetable_plans');
    }
};
