<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Tenant isolation: give the per-college tables that were queried globally a
 * college_id so the CollegeScope keeps each college's data to itself (no more
 * cross-college bleed on dashboards — recent activity, support tickets, exams,
 * payroll, timetables, inventory, result queries).
 *
 * Idempotent (hasColumn-guarded) and safe to re-run. Existing rows are
 * backfilled from their owning relation, then any stragglers are stamped onto
 * the primary college.
 */
return new class extends Migration
{
    /** table => [source table, foreign key] used to backfill college_id (null = primary-college only). */
    private array $map = [
        'activity_logs'   => ['users', 'user_id'],
        'support_tickets' => ['users', 'user_id'],
        'payslips'        => ['users', 'user_id'],
        'timetable_plans' => ['users', 'generated_by'],
        'exams'           => ['subjects', 'subject_id'],
        'result_queries'  => ['students', 'student_id'],
        'inventory_items' => null,
    ];

    public function up(): void
    {
        // 1. Add the column where missing.
        foreach (array_keys($this->map) as $table) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'college_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->unsignedBigInteger('college_id')->nullable()->index()->after('id');
                });
            }
        }

        // 2. Relational backfill for existing rows.
        foreach ($this->map as $table => $source) {
            if (! $source || ! Schema::hasTable($table) || ! Schema::hasColumn($table, 'college_id')) {
                continue;
            }
            [$srcTable, $fk] = $source;
            if (Schema::hasTable($srcTable) && Schema::hasColumn($table, $fk)) {
                DB::statement(
                    "UPDATE {$table} SET college_id = (SELECT {$srcTable}.college_id FROM {$srcTable} WHERE {$srcTable}.id = {$table}.{$fk}) WHERE college_id IS NULL"
                );
            }
        }

        // 3. Safety net: stamp any still-null rows onto the primary college.
        $primary = DB::table('colleges')->where('acronym', 'MAHHFAZ')->value('id')
            ?? DB::table('colleges')->orderBy('id')->value('id');
        if ($primary) {
            foreach (array_keys($this->map) as $table) {
                if (Schema::hasTable($table) && Schema::hasColumn($table, 'college_id')) {
                    DB::table($table)->whereNull('college_id')->update(['college_id' => $primary]);
                }
            }
        }
    }

    public function down(): void
    {
        foreach (array_keys($this->map) as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'college_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropColumn('college_id');
                });
            }
        }
    }
};
