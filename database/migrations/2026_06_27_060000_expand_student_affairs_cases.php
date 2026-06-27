<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_affairs_cases', function (Blueprint $table) {
            $table->text('recommendation')->nullable();
            $table->string('penalty_type')->nullable();
            $table->text('resolution')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->timestamp('resolution_date')->nullable();
            $table->timestamp('forwarded_to_registrar_at')->nullable();
            $table->timestamp('forwarded_to_provost_at')->nullable();
            $table->text('registrar_resolution')->nullable();
            $table->text('provost_resolution')->nullable();
            $table->text('final_resolution')->nullable();
            $table->timestamp('student_notified_at')->nullable();
        });

        Schema::create('student_affairs_register', function (Blueprint $table) {
            $table->id();
            $table->foreignId('college_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('registered_by')->nullable();
            $table->json('checklist')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('registered_at')->nullable();
            $table->timestamps();

            $table->unique(['college_id', 'student_id'], 'sa_register_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_affairs_register');

        Schema::table('student_affairs_cases', function (Blueprint $table) {
            $cols = [
                'recommendation', 'penalty_type', 'resolution', 'resolved_by',
                'resolution_date', 'forwarded_to_registrar_at', 'forwarded_to_provost_at',
                'registrar_resolution', 'provost_resolution', 'final_resolution', 'student_notified_at',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('student_affairs_cases', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
