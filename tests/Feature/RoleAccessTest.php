<?php

namespace Tests\Feature;

use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCollegeFixtures;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase, CreatesCollegeFixtures;

    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->bootCollege();
        $this->student = $this->studentRecord();
    }

    // ---- Proprietor: can VIEW the oversight pages ----

    public function test_proprietor_can_view_core_pages(): void
    {
        // Settings is now MIS-only, so it is no longer part of proprietor oversight.
        $p = $this->userWithRole('proprietor');
        foreach (['/dashboard', '/students', '/staff', '/announcements'] as $path) {
            $this->actingAs($p)->get($path)->assertOk();
        }
    }

    // ---- Proprietor: cannot CHANGE anything (read-only oversight) ----

    /** @dataProvider proprietorBlockedWrites */
    public function test_proprietor_writes_are_blocked(string $method, string $path): void
    {
        $this->actingAs($this->userWithRole('proprietor'))
            ->call($method, $path, ['_token' => csrf_token()])
            ->assertForbidden();
    }

    public static function proprietorBlockedWrites(): array
    {
        return [
            'update settings'   => ['put', '/settings'],
            'post announcement' => ['post', '/announcements'],
            'create staff'      => ['post', '/staff'],
        ];
    }

    public function test_proprietor_can_manage_own_profile(): void
    {
        // Self-service writes are exempt from the read-only rule.
        $this->actingAs($this->userWithRole('proprietor'))->patch('/profile', [
            'name' => 'New Name',
            'email' => 'proprietor@mahhfaz.edu',
        ])->assertSessionHasNoErrors();
    }

    // ---- Oversight roles: student list + payment history only, no academic report ----

    public function test_proprietor_can_view_student_payment_history(): void
    {
        $this->actingAs($this->userWithRole('proprietor'))
            ->get('/students/'.$this->student->id)
            ->assertOk();
    }

    public function test_oversight_roles_cannot_view_end_of_term_report(): void
    {
        foreach (['proprietor', 'provost'] as $role) {
            $this->actingAs($this->userWithRole($role))
                ->get('/students/'.$this->student->id.'/report-card')
                ->assertForbidden();
        }
    }

    // ---- Cross-role write isolation ----

    public function test_lecturer_cannot_take_payments(): void
    {
        $this->actingAs($this->userWithRole('lecturer'))
            ->get('/students/'.$this->student->id.'/pay')
            ->assertForbidden();
    }

    public function test_bursar_can_open_payment_form(): void
    {
        $this->actingAs($this->userWithRole('bursar'))
            ->get('/students/'.$this->student->id.'/pay')
            ->assertOk();
    }

    public function test_lecturer_cannot_delete_students(): void
    {
        // Student deletion is an MIS-only write.
        $this->actingAs($this->userWithRole('lecturer'))
            ->delete('/students/'.$this->student->id)
            ->assertForbidden();
    }

    public function test_bursar_cannot_edit_students(): void
    {
        // Editing student records is MIS-only; the bursar is scoped to finance.
        $this->actingAs($this->userWithRole('bursar'))
            ->put('/students/'.$this->student->id, ['full_name' => 'X'])
            ->assertForbidden();
    }
}
