<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The User model now implements MustVerifyEmail, so the `verified` middleware is
 * active. Every account in the system today was created through a vouched/paid
 * path (admin-created staff, paid applicants/students), so backfill their
 * email_verified_at to avoid locking anyone out on deploy. New accounts are
 * auto-verified at creation; the gate remains a safety net for any future
 * unverified path.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->whereNull('email_verified_at')
            ->update(['email_verified_at' => now()]);
    }

    public function down(): void
    {
        // No-op: we will not un-verify users.
    }
};
