<?php

namespace Tests\Feature;

use App\Models\SchoolClass;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCollegeFixtures;
use Tests\TestCase;

class ClassManagementTest extends TestCase
{
    use RefreshDatabase, CreatesCollegeFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->bootCollege();
    }

    public function test_mis_can_create_a_class(): void
    {
        $this->actingAs($this->userWithRole('mis'))
            ->post('/classes', ['name' => 'UG1C', 'section' => 'UG'])->assertRedirect();

        $this->assertDatabaseHas('classes', ['name' => 'UG1C', 'section' => 'UG', 'active' => true]);
    }

    public function test_lecturer_cannot_create_a_class(): void
    {
        // Class registry management is MIS-only.
        $this->actingAs($this->userWithRole('lecturer'))
            ->post('/classes', ['name' => 'UG1D', 'section' => 'UG'])->assertForbidden();
    }

    public function test_registrar_assignment_persists_to_pivot(): void
    {
        // Staff assignments are managed by the Registrar (manage_staff).
        $registrar = $this->userWithRole('registrar');
        $lecturer = $this->userWithRole('lecturer');

        $classIds = [
            SchoolClass::create(['name' => 'UG1A', 'section' => 'UG', 'level' => 'UG', 'active' => true])->id,
            SchoolClass::create(['name' => 'UG1B', 'section' => 'UG', 'level' => 'UG', 'active' => true])->id,
        ];
        $subjectIds = [
            Subject::create(['name' => 'Anatomy'])->id,
            Subject::create(['name' => 'Physiology'])->id,
        ];

        $this->actingAs($registrar)->post("/staff/{$lecturer->id}/assignments", [
            'class_ids' => $classIds, 'subject_ids' => $subjectIds,
        ])->assertRedirect();

        $lecturer->refresh();
        $this->assertEqualsCanonicalizing($classIds, $lecturer->classes->pluck('id')->all());
        $this->assertEqualsCanonicalizing($subjectIds, $lecturer->subjects->pluck('id')->all());

        // Unassigning all clears the pivot.
        $this->actingAs($registrar)->post("/staff/{$lecturer->id}/assignments", [])->assertRedirect();
        $this->assertCount(0, $lecturer->fresh()->classes);
    }
}
