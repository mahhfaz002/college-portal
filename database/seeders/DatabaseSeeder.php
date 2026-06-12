<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Clean tertiary install: branding settings + MAHHFAZ college, its
        // academic structure and the minimal bootstrap logins. No demo students
        // or demo staff are seeded (DemoDataSeeder / ClassSeeder intentionally
        // excluded) so the new system starts with empty student & staff lists.
        $this->call([
            SettingsSeeder::class,
            UserSeeder::class,
        ]);

        // Safety net: stamp any college-less rows onto the primary college.
        // NOTE: the platform super-admin intentionally has college_id = null
        // (it spans all tenants), so it is excluded from the users backfill.
        $primary = \App\Models\College::where('acronym', 'MAHHFAZ')->value('id');
        if ($primary) {
            foreach (['students', 'payments', 'fee_bills', 'subjects', 'announcements'] as $table) {
                if (\Illuminate\Support\Facades\Schema::hasColumn($table, 'college_id')) {
                    \Illuminate\Support\Facades\DB::table($table)->whereNull('college_id')->update(['college_id' => $primary]);
                }
            }
            \Illuminate\Support\Facades\DB::table('users')
                ->whereNull('college_id')->where('role', '!=', 'superadmin')
                ->update(['college_id' => $primary]);
        }

        // You can keep or remove the default test user below
        /*
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        */
    }
}
