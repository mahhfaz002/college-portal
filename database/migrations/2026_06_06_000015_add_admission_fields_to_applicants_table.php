<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            if (!Schema::hasColumn('applicants', 'passport')) {
                $table->longText('passport')->nullable();   // base64 data URI
            }
            if (!Schema::hasColumn('applicants', 'admission_number')) {
                $table->string('admission_number')->nullable(); // assigned on admit
            }
        });
    }

    public function down(): void
    {
        Schema::table('applicants', function (Blueprint $table) {
            foreach (['passport', 'admission_number'] as $col) {
                if (Schema::hasColumn('applicants', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
