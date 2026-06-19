<?php

namespace Tests\Feature;

use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCollegeFixtures;
use Tests\TestCase;

class RoleScopingTest extends TestCase
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

    // ---- Bursar: finance only — no courses/scores ----

    public function test_bursar_cannot_manage_subjects_or_scores(): void
    {
        $b = $this->userWithRole('bursar');
        $this->actingAs($b)->get('/subjects')->assertForbidden();
        $this->actingAs($b)->get('/scores/entry')->assertForbidden();
        $this->actingAs($b)->post('/subjects', ['name' => 'X'])->assertForbidden();
    }

    public function test_bursar_keeps_fees_and_announcements(): void
    {
        $b = $this->userWithRole('bursar');
        $this->actingAs($b)->get('/fees/orders')->assertOk();
        $this->actingAs($b)->get('/announcements')->assertOk();
    }

    // ---- MIS: owns the class registry and student records ----

    public function test_mis_manages_classes_and_edits_students(): void
    {
        $mis = $this->userWithRole('mis');

        $this->actingAs($mis)->post('/classes', ['name' => 'UG1B', 'section' => 'UG'])->assertRedirect();

        $this->actingAs($mis)->put("/students/{$this->student->id}", [
            'full_name'        => 'Renamed Student',
            'admission_number' => $this->student->admission_number,
            'class_arm'        => $this->student->class_arm,
            'parent_phone'     => $this->student->parent_phone,
            'fees_balance'     => $this->student->fees_balance,
        ])->assertRedirect();

        $this->assertSame('Renamed Student', $this->student->fresh()->full_name);
    }

    // ---- Academic Secretary owns courses; MIS does not (capability split) ----

    public function test_academic_secretary_manages_subjects(): void
    {
        $this->actingAs($this->userWithRole('academic_secretary'))
            ->post('/subjects', ['name' => 'Civic Education'])
            ->assertRedirect();

        $this->assertDatabaseHas('subjects', ['name' => 'Civic Education']);
    }

    public function test_mis_cannot_manage_subjects(): void
    {
        // Course creation moved to the Academic Secretary; MIS is excluded.
        $this->actingAs($this->userWithRole('mis'))
            ->post('/subjects', ['name' => 'Should Be Blocked'])
            ->assertForbidden();
    }
}
