<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('colleges', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('acronym', 20)->nullable();
            $table->string('logo_path')->nullable();
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('primary_color', 20)->nullable();
            $table->string('paystack_public_key')->nullable();
            $table->string('paystack_secret_key')->nullable();
            // e.g. {acronym}/{year}/{dept}/{program}/{serial}
            $table->string('registration_no_format')->default('{acronym}/{year}/{dept}/{program}/{serial}');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('colleges');
    }
};
