<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Nigerian colleges typically charge a higher registration fee for a new
 * student's first semester (100 level) than for later semesters. Split the
 * single registration_fee into:
 *   registration_fee_first  — first semester, 100 level (new intake)
 *   registration_fee_other  — every subsequent semester
 * Existing rows are backfilled so both equal the old single fee.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            if (! Schema::hasColumn('programs', 'registration_fee_first')) {
                $table->decimal('registration_fee_first', 12, 2)->default(0);
            }
            if (! Schema::hasColumn('programs', 'registration_fee_other')) {
                $table->decimal('registration_fee_other', 12, 2)->default(0);
            }
        });

        if (Schema::hasColumn('programs', 'registration_fee')) {
            DB::statement('UPDATE programs SET registration_fee_first = registration_fee, registration_fee_other = registration_fee');
        }
    }

    public function down(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            foreach (['registration_fee_first', 'registration_fee_other'] as $col) {
                if (Schema::hasColumn('programs', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
