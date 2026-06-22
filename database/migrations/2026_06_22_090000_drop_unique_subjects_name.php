<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Course titles (and codes) legitimately repeat across programmes and levels —
 * e.g. "Anatomy" or "Use of English" exists in several courses of study. The
 * original subjects.name UNIQUE index made the second one a hard duplicate-key
 * 500 when the academic secretary tried to add it. Drop it; uniqueness within a
 * single (programme, level, semester) cohort is already enforced by the builder
 * (it re-syncs that cohort on save).
 */
return new class extends Migration
{
    public function up(): void
    {
        try {
            Schema::table('subjects', fn (Blueprint $t) => $t->dropUnique('subjects_name_unique'));
        } catch (\Throwable $e) {
            // Index already absent (fresh schema / re-run) — nothing to do.
        }
    }

    public function down(): void
    {
        // Intentionally NOT re-added: a global unique on course name is wrong for
        // a multi-programme college.
    }
};
