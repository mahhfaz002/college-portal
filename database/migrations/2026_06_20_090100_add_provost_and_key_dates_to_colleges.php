<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-college provost photo + editable key dates. The provost photo, name and
 * message are MIS-editable (their own college); key_dates feed the homepage's
 * "Key Dates & Timeline" section, unique to each college.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('colleges', function (Blueprint $table) {
            if (! Schema::hasColumn('colleges', 'provost_photo')) {
                $table->text('provost_photo')->nullable();   // base64 or stored path
            }
            if (! Schema::hasColumn('colleges', 'key_dates')) {
                $table->json('key_dates')->nullable();        // [{title, date}, ...]
            }
        });
    }

    public function down(): void
    {
        Schema::table('colleges', function (Blueprint $table) {
            foreach (['provost_photo', 'key_dates'] as $col) {
                if (Schema::hasColumn('colleges', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
