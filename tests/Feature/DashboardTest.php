<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCollegeFixtures;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase, CreatesCollegeFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->bootCollege();
    }

    /** @dataProvider roleProvider */
    public function test_dashboard_renders_for_each_role(string $role): void
    {
        if ($role === 'student') {
            // The student dashboard resolves the pupil's record by email.
            $this->studentRecord(['email' => 'dash.student@example.test']);
            $user = $this->userWithRole('student', ['email' => 'dash.student@example.test']);
        } else {
            $user = $this->userWithRole($role);
        }

        $this->actingAs($user)->get('/dashboard')->assertOk();
    }

    public static function roleProvider(): array
    {
        // Current college roles (see App\Support\Permissions).
        return [
            ['proprietor'], ['provost'], ['registrar'], ['bursar'], ['mis'],
            ['academic_secretary'], ['exam_officer'], ['lecturer'], ['hod'],
            ['assistant_hod'], ['student_affairs'], ['office_secretary'],
            ['admission_officer'], ['librarian'], ['student'],
        ];
    }

    public function test_announcements_page_loads(): void
    {
        $this->actingAs($this->userWithRole('lecturer'))->get('/announcements')->assertOk();
    }

    public function test_settings_page_is_mis_only(): void
    {
        // Settings moved to MIS-only; even the proprietor (oversight) is excluded.
        $this->actingAs($this->userWithRole('lecturer'))->get('/settings')->assertForbidden();
        $this->actingAs($this->userWithRole('proprietor'))->get('/settings')->assertForbidden();
        $this->actingAs($this->userWithRole('mis'))->get('/settings')->assertOk();
    }

    public function test_mis_can_update_settings(): void
    {
        $this->actingAs($this->userWithRole('mis'))->put('/settings', [
            'school_name' => 'New School Name',
            'currency_symbol' => '$',
        ])->assertSessionHasNoErrors();

        $this->assertSame('New School Name', setting('school_name'));
        $this->assertSame('$', setting('currency_symbol'));
    }
}
