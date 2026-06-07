<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function userWithRole(string $role): User
    {
        return User::create([
            'name' => ucfirst($role) . ' User',
            'email' => $role . '@test.local',
            'password' => bcrypt('password'),
            'role' => $role,
            'must_change_password' => false,
        ]);
    }

    /** @dataProvider roleProvider */
    public function test_dashboard_renders_for_each_role(string $role): void
    {
        $user = $this->userWithRole($role);
        $response = $this->actingAs($user)->get('/dashboard');
        $response->assertOk();
    }

    public static function roleProvider(): array
    {
        return [
            ['proprietor'],
            ['principal'],
            ['admin'],
            ['ict'],
            ['accountant'],
            ['teacher'],
            ['student'],
            ['exam_officer'],
        ];
    }

    public function test_announcements_page_loads(): void
    {
        $user = $this->userWithRole('teacher');
        $this->actingAs($user)->get('/announcements')->assertOk();
    }

    public function test_settings_page_requires_admin_role(): void
    {
        $teacher = $this->userWithRole('teacher');
        $this->actingAs($teacher)->get('/settings')->assertForbidden();

        // Proprietor may VIEW settings (oversight) ...
        $proprietor = $this->userWithRole('proprietor');
        $this->actingAs($proprietor)->get('/settings')->assertOk();
    }

    public function test_proprietor_cannot_update_settings(): void
    {
        // ... but the read-only rule blocks any write, even settings.
        $user = $this->userWithRole('proprietor');
        $this->actingAs($user)->put('/settings', [
            'school_name' => 'New School Name',
            'currency_symbol' => '$',
        ])->assertForbidden();
    }

    public function test_principal_can_update_settings(): void
    {
        $user = $this->userWithRole('principal');
        $this->actingAs($user)->put('/settings', [
            'school_name' => 'New School Name',
            'currency_symbol' => '$',
        ])->assertSessionHasNoErrors();

        $this->assertSame('New School Name', setting('school_name'));
        $this->assertSame('$', setting('currency_symbol'));
    }
}
