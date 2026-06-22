<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `students.photo` was a string (VARCHAR(255) on MySQL), but registration copies
 * the applicant's passport — a base64 data-URI well over 255 chars (stored as
 * longText on `applicants.passport` / `users.passport`). On MySQL that overflow
 * threw "Data too long for column 'photo'", 500-ing the registration-fee callback
 * so paid students never reached their dashboard. (SQLite ignores the length,
 * hiding it locally.) Widen to longText to match the source column.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('students', 'photo')) {
            Schema::table('students', function (Blueprint $table) {
                $table->longText('photo')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        // Intentionally not narrowing back — would truncate stored photos.
    }
};
