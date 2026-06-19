<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCollegeFixtures;
use Tests\TestCase;

class ModuleSmokeTest extends TestCase
{
    use RefreshDatabase, CreatesCollegeFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->bootCollege();
    }

    /** @dataProvider pageProvider */
    public function test_page_renders(string $path, string $role): void
    {
        $this->actingAs($this->userWithRole($role))->get($path)->assertOk();
    }

    /**
     * Each page is loaded as a role that legitimately owns or oversees it
     * (see App\Support\Permissions). Removed pages (student create, attendance)
     * are intentionally absent.
     */
    public static function pageProvider(): array
    {
        return [
            'students'      => ['/students', 'proprietor'],
            'subjects'      => ['/subjects', 'mis'],
            'departments'   => ['/departments', 'registrar'],
            'staff'         => ['/staff', 'proprietor'],
            'classes'       => ['/classes', 'mis'],
            'announcements' => ['/announcements', 'proprietor'],
            'settings'      => ['/settings', 'mis'],
            'timetable'     => ['/timetable', 'proprietor'],
            'library'       => ['/library', 'proprietor'],
            'exams'         => ['/exams', 'exam_officer'],
            'transport'     => ['/transport', 'proprietor'],
            'alumni'        => ['/alumni', 'proprietor'],
            'admissions'    => ['/admin/admissions', 'registrar'],
            'inventory'     => ['/inventory', 'mis'],
            'fees.orders'   => ['/fees/orders', 'bursar'],
        ];
    }

    public function test_announcement_lifecycle(): void
    {
        // MIS authors announcements (proprietor is read-only oversight).
        $this->actingAs($this->userWithRole('mis'))->post('/announcements', [
            'title' => 'Resumption',
            'body' => 'College resumes Monday.',
            'audience' => 'all',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('announcements', ['title' => 'Resumption']);
    }
}
