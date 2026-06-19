<?php

namespace Tests\Feature;

use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCollegeFixtures;
use Tests\TestCase;

class StaffManagementTest extends TestCase
{
    use RefreshDatabase, CreatesCollegeFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->bootCollege();
    }

    private function registrar(): User
    {
        // The Registrar owns staff registration & management (manage_staff).
        return $this->userWithRole('registrar');
    }

    public function test_registrar_can_open_registration_form(): void
    {
        $this->actingAs($this->registrar())->get('/staff-register')->assertOk();
    }

    public function test_non_registrar_cannot_open_registration_form(): void
    {
        $this->actingAs($this->userWithRole('lecturer'))->get('/staff-register')->assertForbidden();
        $this->actingAs($this->userWithRole('proprietor'))->get('/staff-register')->assertForbidden();
    }

    public function test_registrar_registers_lecturer_with_generated_email_and_staff_id(): void
    {
        $this->actingAs($this->registrar())->post('/staff', [
            'first_name'    => 'John',
            'surname'       => 'Doe',
            'role'          => 'lecturer',
            'employed_year' => '2026',
        ])->assertRedirect();

        $user = User::where('surname', 'Doe')->firstOrFail();

        // Email = first initial + surname @ the college/staff domain.
        $this->assertSame('jdoe@'.setting('staff_email_domain'), $user->email);

        // Staff ID = ACRONYM/YEAR/SEQ.
        $this->assertStringStartsWith(setting('school_acronym').'/2026/', $user->staff_id);

        // Must change the temp password on first login.
        $this->assertTrue($user->must_change_password);
    }

    public function test_login_email_collision_gets_numeric_suffix(): void
    {
        $registrar = $this->registrar();
        $domain = setting('staff_email_domain');

        $this->actingAs($registrar)->post('/staff', ['first_name' => 'Jane', 'surname' => 'Smith', 'role' => 'lecturer']);
        $this->actingAs($registrar)->post('/staff', ['first_name' => 'Jane', 'surname' => 'Smith', 'role' => 'lecturer']);

        $emails = User::where('surname', 'Smith')->pluck('email')->all();
        $this->assertContains('jsmith@'.$domain, $emails);
        $this->assertContains('jsmith1@'.$domain, $emails);
    }

    public function test_registrar_can_update_assignments(): void
    {
        $lecturer = $this->userWithRole('lecturer');
        $class = SchoolClass::create(['name' => 'UG1A', 'section' => 'UG', 'level' => 'UG', 'active' => true]);

        $this->actingAs($this->registrar())
            ->post("/staff/{$lecturer->id}/assignments", ['class_ids' => [$class->id]])
            ->assertRedirect();

        $this->assertTrue($lecturer->fresh()->classes->contains($class->id));
    }

    public function test_registrar_can_delete_staff_but_not_self(): void
    {
        $registrar = $this->registrar();
        $lecturer = $this->userWithRole('lecturer');

        $this->actingAs($registrar)->delete("/staff/{$lecturer->id}")->assertRedirect();
        $this->assertDatabaseMissing('users', ['id' => $lecturer->id]);

        $this->actingAs($registrar)->delete("/staff/{$registrar->id}")->assertRedirect();
        $this->assertDatabaseHas('users', ['id' => $registrar->id]); // self-delete blocked
    }
}
