<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Exam Mode" — one active cycle per college. The Exam Officer sets the exam
 * start; the question-submission deadline is derived as 5 days before that.
 * Drives the countdown timers shown on every dashboard.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_cycles', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('college_id')->nullable()->index();
            $table->string('title')->default('Examinations');
            $table->dateTime('exam_start_at');
            $table->dateTime('submission_deadline_at');     // exam_start_at - 5 days
            $table->string('status')->default('active');     // active | closed
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_cycles');
    }
};
