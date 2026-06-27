<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Clean platform seed — ONLY the platform super-admin.
 *
 * The super-admin has no college (college_id = null) so the CollegeScope is a
 * no-op and it spans every tenant. From its dashboard it registers colleges and
 * creates each college's leadership accounts (proprietor, provost, registrar,
 * bursar, mis, academic secretary); the college's Registrar then creates the
 * rest of the staff. No sample colleges, staff or students are seeded.
 *
 * Super-admin login:
 *   superadmin@mahhfaz.com / password   (change the password on first login)
 *
 * IMPORTANT: this seeder is CREATE-ONLY. If a super-admin already exists it
 * does nothing — so re-running seeds (e.g. on every deploy) can NEVER reset the
 * super-admin's username or password back to the default. Once the password is
 * changed on the dashboard, that change is permanent. This is deliberate: with
 * colleges about to onboard, a default/weak super-admin credential must not be
 * silently restored by a deploy.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        if (User::withoutGlobalScopes()->where('role', 'superadmin')->exists()) {
            return;
        }

        User::create([
            'name'                 => 'Mafindi Mustapha Hussaini',
            'email'                => 'superadmin@mahhfaz.com',
            'password'             => Hash::make('password'),
            'role'                 => 'superadmin',
            'college_id'           => null,
            'platform_fee_paid'    => true,
            'must_change_password' => true,   // force a strong password on first login
            'email_verified_at'    => now(),
        ]);
    }
}
