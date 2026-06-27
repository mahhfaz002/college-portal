<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SuperAdminSeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_creates_the_owner_super_admin(): void
    {
        $this->seed(UserSeeder::class);

        $admins = User::withoutGlobalScopes()->where('role', 'superadmin')->get();
        $this->assertCount(1, $admins);

        $admin = $admins->first();
        $this->assertSame('superadmin@mahhfaz.com', $admin->email);
        $this->assertSame('Mafindi Mustapha Hussaini', $admin->name);
    }

    public function test_reseeding_never_resets_username_or_password(): void
    {
        $this->seed(UserSeeder::class);

        // Owner changes their password (and email) on the dashboard.
        $admin = User::withoutGlobalScopes()->where('role', 'superadmin')->first();
        $admin->forceFill([
            'email'    => 'owner@secure.example',
            'password' => Hash::make('a-strong-secret'),
        ])->save();

        // A later deploy re-runs the seeder — it must be a no-op for credentials.
        $this->seed(UserSeeder::class);

        $admins = User::withoutGlobalScopes()->where('role', 'superadmin')->get();
        $this->assertCount(1, $admins, 'reseeding must not create a duplicate super-admin');

        $fresh = $admins->first();
        $this->assertSame('owner@secure.example', $fresh->email, 'username must survive reseeding');
        $this->assertTrue(Hash::check('a-strong-secret', $fresh->password), 'password must survive reseeding');
    }
}
