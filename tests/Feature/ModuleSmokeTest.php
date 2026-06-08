<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModuleSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function admin(): User
    {
        // Proprietor can VIEW every page (read-only oversight) — ideal for
        // the GET smoke checks below.
        return User::where('role', 'proprietor')->firstOrFail();
    }

    private function writer(): User
    {
        // Principal can perform writes the proprietor cannot.
        return User::where('role', 'principal')->firstOrFail();
    }

    private function userWithRole(string $role): User
    {
        return User::where('role', $role)->firstOrFail();
    }

    /** @dataProvider pageProvider */
    public function test_page_renders(string $path, string $role): void
    {
        $this->actingAs($this->userWithRole($role))->get($path)->assertOk();
    }

    /**
     * Each page is loaded as a role that legitimately owns or oversees it.
     * Proprietor is used for the broad data-view pages (oversight); the
     * management/form pages are loaded as their owning role.
     */
    public static function pageProvider(): array
    {
        return [
            'students'          => ['/students', 'proprietor'],
            'students.create'   => ['/students/create', 'admin'],
            'subjects'          => ['/subjects', 'proprietor'],
            'attendance'        => ['/attendance', 'proprietor'],
            'attendance report' => ['/attendance/report', 'proprietor'],
            'inventory'         => ['/inventory', 'admin'],
            'inventory.create'  => ['/inventory/create', 'admin'],
            'admissions'        => ['/admin/admissions', 'admin'],
            'promotion'         => ['/promotion', 'admin'],
            'scores.entry'      => ['/scores/entry', 'teacher'],
            'announcements'     => ['/announcements', 'proprietor'],
            'settings'          => ['/settings', 'principal'],
            'staff'             => ['/staff', 'principal'],
            'timetable'         => ['/timetable', 'proprietor'],
            'library'           => ['/library', 'proprietor'],
            'exams'             => ['/exams', 'proprietor'],
            'transport'         => ['/transport', 'proprietor'],
            'alumni'            => ['/alumni', 'proprietor'],
        ];
    }

    public function test_announcement_lifecycle(): void
    {
        $admin = $this->writer();

        $this->actingAs($admin)->post('/announcements', [
            'title' => 'Resumption',
            'body' => 'School resumes Monday.',
            'audience' => 'all',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('announcements', ['title' => 'Resumption']);
    }
}
