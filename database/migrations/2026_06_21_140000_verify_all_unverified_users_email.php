<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Backfill: mark every remaining unverified account as verified.
 *
 * The portal has no open registration — accounts are created by admins (vouched)
 * or after a confirmed payment — but `email_verified_at` had been dropped from the
 * User $fillable, so accounts created via `User::create([...])` were silently left
 * unverified and stranded on /verify-email (an earlier one-off backfill only fixed
 * accounts that existed at that time). The User model now verifies on creation;
 * this clears the backlog for accounts created in between.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->whereNull('email_verified_at')->update(['email_verified_at' => now()]);
    }

    public function down(): void
    {
        // No-op: we never want to un-verify accounts.
    }
};
