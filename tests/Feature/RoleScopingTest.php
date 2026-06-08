<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleScopingTest extends TestCase
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

    // ---- Bursar: fees only, no subjects/attendance/scores ----

    public function test_bursar_cannot_see_subjects_or_attendance(): void
    {
        $b = $this->as('accountant');
        $this->actingAs($b)->get('/subjects')->assertForbidden();
        $this->actingAs($b)->get('/attendance')->assertForbidden();
        $this->actingAs($b)->get('/attendance/report')->assertForbidden();
    }

    public function test_bursar_cannot_enter_scores_or_manage_subjects(): void
    {
        $b = $this->as('accountant');
        $this->actingAs($b)->get('/scores/entry')->assertForbidden();
        $this->actingAs($b)->post('/subjects', ['name' => 'X'])->assertForbidden();
    }

    public function test_bursar_keeps_fees_and_announcements(): void
    {
        $b = $this->as('accountant');
        $this->actingAs($b)->get('/fees')->assertOk();
        $this->actingAs($b)->get('/announcements')->assertOk();
    }

    // ---- ICT: no attendance/results; can edit students, manage subjects/classes ----

    public function test_ict_cannot_see_attendance(): void
    {
        $this->actingAs($this->as('ict'))->get('/attendance')->assertForbidden();
    }

    public function test_ict_can_manage_subjects_and_classes_and_edit_students(): void
    {
        $ict = $this->as('ict');
        $this->actingAs($ict)->post('/subjects', ['name' => 'Civic Education'])->assertRedirect();
        $this->actingAs($ict)->post('/classes', ['name' => 'JSS3B'])->assertRedirect();

        $student = \App\Models\Student::firstOrFail();
        $this->actingAs($ict)->put("/students/{$student->id}", [
            'full_name' => 'Renamed Pupil',
            'admission_number' => $student->admission_number,
            'class_arm' => 'JSS2A',
            'parent_phone' => $student->parent_phone,
            'fees_balance' => $student->fees_balance,
        ])->assertRedirect();
        $this->assertSame('Renamed Pupil', $student->fresh()->full_name);
    }

    public function test_ict_cannot_admit_or_delete_students(): void
    {
        $ict = $this->as('ict');
        $this->actingAs($ict)->post('/students', [])->assertForbidden();
        $student = \App\Models\Student::firstOrFail();
        $this->actingAs($ict)->delete("/students/{$student->id}")->assertForbidden();
    }
}
