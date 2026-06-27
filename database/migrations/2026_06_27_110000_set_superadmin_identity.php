<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;

/**
 * Set the platform super-admin's identity to the live owner's details:
 *   name  → Mafindi Mustapha Hussaini
 *   email → superadmin@mahhfaz.com
 *
 * The PASSWORD IS DELIBERATELY NOT TOUCHED — if the owner has already set a
 * strong password on the dashboard, it stays; if still on the default, they
 * change it themselves. This migration only renames the account.
 */
return new class extends Migration
{
    public function up(): void
    {
        $admin = User::withoutGlobalScopes()->where('role', 'superadmin')->orderBy('id')->first();
        if (!$admin) {
            return; // fresh installs get the right identity from UserSeeder
        }

        $updates = ['name' => 'Mafindi Mustapha Hussaini'];

        // Only claim the new email if it isn't already taken by another account.
        $taken = User::withoutGlobalScopes()
            ->where('email', 'superadmin@mahhfaz.com')
            ->where('id', '!=', $admin->id)
            ->exists();

        if (!$taken && $admin->email !== 'superadmin@mahhfaz.com') {
            $updates['email'] = 'superadmin@mahhfaz.com';
        }

        $admin->forceFill($updates)->save();
    }

    public function down(): void
    {
        // No-op: we don't restore the old super-admin email/name.
    }
};
