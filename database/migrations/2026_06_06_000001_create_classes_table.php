<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('classes')) {
            return;
        }

        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();   // e.g. "JSS1A", "SSS3 Science"
            $table->string('level')->nullable(); // e.g. "JSS", "SSS", "Primary"
            $table->string('section')->nullable(); // e.g. "A", "B", "Science"
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
