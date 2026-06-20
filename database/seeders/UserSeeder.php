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
 * Super-admin login (change after first login):
 *   superadmin@mahhfaz.edu.ng / password
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'superadmin@mahhfaz.edu.ng'],
            [
                'name'                 => 'Platform Super Admin',
                'password'             => Hash::make('password'),
                'role'                 => 'superadmin',
                'college_id'           => null,
                'platform_fee_paid'    => true,
                'must_change_password' => false,
                'email_verified_at'    => now(),
            ]
        );
    }
}
