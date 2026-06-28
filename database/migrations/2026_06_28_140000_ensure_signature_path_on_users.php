<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Safety net: GUARANTEE the users.signature_path column exists.
 *
 * The original add-signature migration was recorded as run on at least one
 * environment without the column actually being present, which made the
 * Registrar/Provost "Save signature" POST 500 on the DB write (after the S3
 * upload had already succeeded — hence the ~0.4s before the error). This
 * idempotent migration re-adds the column when missing so the feature works
 * regardless of the earlier migration's recorded state.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'signature_path')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('signature_path')->nullable();
            });
        }
    }

    public function down(): void
    {
        // No-op: dropping is owned by the original migration.
    }
};
