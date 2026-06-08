<?php

namespace Tests\Feature;

use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_principal_can_create_a_class(): void
    {
        $principal = User::where('role', 'principal')->firstOrFail();
        $this->actingAs($principal)->post('/classes', ['name' => 'JSS1C'])->assertRedirect();
        $this->assertDatabaseHas('classes', ['name' => 'JSS1C', 'active' => true]);
    }

    public function test_ict_can_create_a_class_but_teacher_cannot(): void
    {
        $this->actingAs(User::where('role', 'ict')->firstOrFail())
            ->post('/classes', ['name' => 'SSS1B'])->assertRedirect();
        $this->assertDatabaseHas('classes', ['name' => 'SSS1B']);

        $this->actingAs(User::where('role', 'teacher')->firstOrFail())
            ->post('/classes', ['name' => 'SSS1C'])->assertForbidden();
    }

    public function test_principal_assignment_persists_to_pivot(): void
    {
        $principal = User::where('role', 'principal')->firstOrFail();
        $teacher = User::where('role', 'teacher')->firstOrFail();
        $classIds = SchoolClass::take(2)->pluck('id')->all();
        $subjectIds = Subject::take(2)->pluck('id')->all();

        $this->actingAs($principal)->post("/staff/{$teacher->id}/assignments", [
            'class_ids' => $classIds, 'subject_ids' => $subjectIds,
        ])->assertRedirect();

        $teacher->refresh();
        $this->assertEqualsCanonicalizing($classIds, $teacher->classes->pluck('id')->all());
        $this->assertEqualsCanonicalizing($subjectIds, $teacher->subjects->pluck('id')->all());

        // Unassigning all clears the pivot.
        $this->actingAs($principal)->post("/staff/{$teacher->id}/assignments", [])->assertRedirect();
        $this->assertCount(0, $teacher->fresh()->classes);
    }
}
