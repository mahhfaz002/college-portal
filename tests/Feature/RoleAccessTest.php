<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function as(string $role): User
    {
        return User::where('role', $role)->firstOrFail();
    }

    private function student(): Student
    {
        return Student::firstOrFail();
    }

    // ---- Proprietor: can VIEW everything ----

    public function test_proprietor_can_view_core_pages(): void
    {
        $p = $this->as('proprietor');
        foreach (['/dashboard', '/students', '/staff', '/settings', '/announcements'] as $path) {
            $this->actingAs($p)->get($path)->assertOk();
        }
    }

    // ---- Proprietor: cannot CHANGE anything ----

    /** @dataProvider proprietorBlockedWrites */
    public function test_proprietor_writes_are_blocked(string $method, string $path): void
    {
        $this->actingAs($this->as('proprietor'))
            ->call($method, $path, ['_token' => csrf_token()])
            ->assertForbidden();
    }

    public static function proprietorBlockedWrites(): array
    {
        return [
            'create student'   => ['post', '/students'],
            'update settings'  => ['put', '/settings'],
            'post announcement'=> ['post', '/announcements'],
            'create staff'     => ['post', '/staff'],
        ];
    }

    public function test_proprietor_can_manage_own_profile(): void
    {
        // Self-service writes are exempt from the read-only rule.
        $this->actingAs($this->as('proprietor'))->patch('/profile', [
            'name' => 'New Name',
            'email' => 'proprietor@mahhfaz.edu',
        ])->assertSessionHasNoErrors();
    }

    // ---- Cross-role isolation ----

    public function test_teacher_cannot_create_students(): void
    {
        $this->actingAs($this->as('teacher'))->post('/students', [])->assertForbidden();
    }

    public function test_teacher_cannot_take_payments(): void
    {
        $this->actingAs($this->as('teacher'))
            ->get('/students/'.$this->student()->id.'/pay')
            ->assertForbidden();
    }

    public function test_accountant_can_open_payment_form(): void
    {
        $this->actingAs($this->as('accountant'))
            ->get('/students/'.$this->student()->id.'/pay')
            ->assertOk();
    }

    public function test_accountant_cannot_create_students(): void
    {
        $this->actingAs($this->as('accountant'))->post('/students', [])->assertForbidden();
    }

    public function test_admin_can_reach_student_store(): void
    {
        // Registrar/admin owns student writes — should pass the role gate
        // (validation may bounce it back, but it must NOT be 403).
        $this->actingAs($this->as('admin'))->post('/students', [])->assertStatus(302);
    }
}
