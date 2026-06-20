<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pre-loaded admitted-student records (uploaded by the super-admin from a CSV,
 * per college). A self-registering student is matched against these by
 * registration number before an account can be created.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admitted_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('college_id')->index();
            $table->string('full_name');
            $table->string('registration_number');
            $table->string('department')->nullable();
            $table->string('level')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->unsignedBigInteger('claimed_by')->nullable();
            $table->timestamps();

            // A registration number is unique within a college.
            $table->unique(['college_id', 'registration_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admitted_records');
    }
};
