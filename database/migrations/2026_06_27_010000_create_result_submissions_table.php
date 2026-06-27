<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('result_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('college_id')->constrained()->onDelete('cascade');
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('term');
            $table->string('session');
            $table->string('scan_path')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('physical_copy_deadline')->nullable();
            $table->string('status')->default('submitted');
            $table->timestamp('transmitted_at')->nullable();
            $table->foreignId('transmitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['subject_id', 'term', 'session'], 'result_sub_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('result_submissions');
    }
};
