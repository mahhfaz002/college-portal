<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('college_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('acronym', 20)->nullable();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('college_id')->constrained()->cascadeOnDelete();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('acronym', 20)->nullable();
            $table->decimal('application_fee', 12, 2)->default(0);
            $table->decimal('acceptance_fee', 12, 2)->default(0);
            $table->decimal('registration_fee', 12, 2)->default(0);
            // ND/HND/Degree/Certificate level structure label.
            $table->string('level_system')->nullable();
            $table->unsignedTinyInteger('duration_years')->default(2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programs');
        Schema::dropIfExists('departments');
    }
};
