<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('class_attendance_logs')) {
            return;
        }

        Schema::create('class_attendance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // teacher who took it
            $table->string('class_arm');       // class label marked
            $table->date('log_date');
            $table->unsignedInteger('present_count')->default(0);
            $table->unsignedInteger('total_count')->default(0);
            $table->dateTime('taken_at');
            $table->timestamps();
            $table->unique(['user_id', 'class_arm', 'log_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_attendance_logs');
    }
};
