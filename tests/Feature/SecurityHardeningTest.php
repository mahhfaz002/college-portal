<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCollegeFixtures;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase, CreatesCollegeFixtures;

    private Student $ownStudent;
    private Student $otherStudent;
    private User $studentUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->bootCollege();

        $this->ownStudent = $this->studentRecord([
            'full_name' => 'Owner Pupil', 'admission_number' => 'OWN/1', 'email' => 'owner@pupil.test',
        ]);
        $this->otherStudent = $this->studentRecord([
            'full_name' => 'Other Pupil', 'admission_number' => 'OTH/1', 'email' => 'other@pupil.test',
        ]);
        // Student login linked to ownStudent by matching email.
        $this->studentUser = $this->userWithRole('student', [
            'name' => 'Owner Pupil', 'email' => 'owner@pupil.test',
        ]);
    }

    // ---- RLS / IDOR ----

    public function test_student_cannot_open_another_students_record(): void
    {
        $this->actingAs($this->studentUser)
            ->get("/students/{$this->otherStudent->id}/report-card")
            ->assertForbidden();

        $this->actingAs($this->studentUser)
            ->get("/students/{$this->otherStudent->id}")
            ->assertForbidden();
    }

    public function test_student_cannot_download_another_students_report(): void
    {
        $this->actingAs($this->studentUser)
            ->get("/reports/download/{$this->otherStudent->id}")
            ->assertForbidden();
    }

    public function test_student_can_download_own_report(): void
    {
        $this->actingAs($this->studentUser)
            ->get("/reports/download/{$this->ownStudent->id}")
            ->assertOk();
    }

    public function test_staff_can_download_any_report(): void
    {
        // Report download requires the view_students capability — lecturer is
        // excluded, so this exercises a viewing role (registrar).
        $this->actingAs($this->userWithRole('registrar'))
            ->get("/reports/download/{$this->otherStudent->id}")
            ->assertOk();
    }

    // ---- Security headers ----

    public function test_security_headers_present(): void
    {
        $this->get('/login')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }
}
