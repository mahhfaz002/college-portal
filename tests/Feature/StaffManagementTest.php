<?php

namespace Tests\Feature;

use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function principal(): User
    {
        return User::where('role', 'principal')->firstOrFail();
    }

    public function test_principal_can_open_registration_form(): void
    {
        $this->actingAs($this->principal())->get('/staff-register')->assertOk();
    }

    public function test_non_principal_cannot_open_registration_form(): void
    {
        $teacher = User::where('role', 'teacher')->firstOrFail();
        $this->actingAs($teacher)->get('/staff-register')->assertForbidden();

        $proprietor = User::where('role', 'proprietor')->firstOrFail();
        $this->actingAs($proprietor)->get('/staff-register')->assertForbidden();
    }

    public function test_principal_registers_teacher_with_generated_email_and_staff_id(): void
    {
        $class = SchoolClass::where('name', 'JSS1A')->firstOrFail();
        $subjects = Subject::take(2)->pluck('id')->all();

        $this->actingAs($this->principal())->post('/staff', [
            'first_name'   => 'John',
            'surname'      => 'Doe',
            'role'         => 'teacher',
            'employed_year'=> '2026',
            'class_ids'    => [$class->id],
            'subject_ids'  => $subjects,
        ])->assertRedirect();

        $user = User::where('surname', 'Doe')->firstOrFail();

        // Email = first initial + surname @ domain.
        $domain = setting('staff_email_domain');
        $this->assertSame('jdoe@'.$domain, $user->email);

        // Staff ID = ACRONYM/YEAR/SEQ.
        $this->assertStringStartsWith(setting('school_acronym').'/2026/', $user->staff_id);

        // Must change password on first login.
        $this->assertTrue($user->must_change_password);

        // Assignments wired through pivots.
        $this->assertTrue($user->classes->contains($class->id));
        $this->assertCount(2, $user->subjects);
    }

    public function test_login_email_collision_gets_numeric_suffix(): void
    {
        $principal = $this->principal();
        $domain = setting('staff_email_domain');

        $this->actingAs($principal)->post('/staff', ['first_name' => 'Jane', 'surname' => 'Smith', 'role' => 'teacher']);
        $this->actingAs($principal)->post('/staff', ['first_name' => 'Jane', 'surname' => 'Smith', 'role' => 'teacher']);

        $emails = User::where('surname', 'Smith')->pluck('email')->all();
        $this->assertContains('jsmith@'.$domain, $emails);
        $this->assertContains('jsmith1@'.$domain, $emails);
    }

    public function test_principal_can_update_assignments(): void
    {
        $teacher = User::where('role', 'teacher')->firstOrFail();
        $class = SchoolClass::where('name', 'JSS2A')->firstOrFail();

        $this->actingAs($this->principal())
            ->post("/staff/{$teacher->id}/assignments", ['class_ids' => [$class->id]])
            ->assertRedirect();

        $this->assertTrue($teacher->fresh()->classes->contains($class->id));
    }

    public function test_principal_can_delete_staff_but_not_self(): void
    {
        $principal = $this->principal();
        $teacher = User::where('role', 'teacher')->firstOrFail();

        $this->actingAs($principal)->delete("/staff/{$teacher->id}")->assertRedirect();
        $this->assertDatabaseMissing('users', ['id' => $teacher->id]);

        $this->actingAs($principal)->delete("/staff/{$principal->id}")->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $principal->id]); // self-delete blocked
    }
}
