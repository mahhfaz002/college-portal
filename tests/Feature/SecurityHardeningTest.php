<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    private Student $ownStudent;
    private Student $otherStudent;
    private User $studentUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->ownStudent = Student::create([
            'full_name' => 'Owner Pupil', 'admission_number' => 'OWN/1', 'class_arm' => 'JSS1A',
            'parent_phone' => '080', 'fees_balance' => 0, 'email' => 'owner@pupil.local',
        ]);
        $this->otherStudent = Student::create([
            'full_name' => 'Other Pupil', 'admission_number' => 'OTH/1', 'class_arm' => 'JSS1A',
            'parent_phone' => '081', 'fees_balance' => 0, 'email' => 'other@pupil.local',
        ]);
        $this->studentUser = User::create([
            'name' => 'Owner Pupil', 'email' => 'owner@pupil.local',
            'password' => bcrypt('password'), 'role' => 'student', 'must_change_password' => false,
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
        $teacher = User::where('role', 'teacher')->firstOrFail();
        $this->actingAs($teacher)
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

    // ---- Rate limiting ----

    public function test_exam_unlock_is_rate_limited(): void
    {
        $exam = Exam::create([
            'subject_id' => Subject::first()->id, 'title' => 'T', 'term' => '', 'session' => '',
            'class_arms' => ['JSS1A'], 'duration_minutes' => 30, 'status' => 'released', 'access_password' => 'pw',
        ]);

        $hitLimited = false;
        for ($i = 0; $i < 12; $i++) {
            $res = $this->actingAs($this->studentUser)
                ->post("/my-exams/{$exam->id}/unlock", ['access_password' => 'wrong']);
            if ($res->status() === 429) {
                $hitLimited = true;
                break;
            }
        }

        $this->assertTrue($hitLimited, 'Expected exam unlock to be throttled (429) after repeated attempts.');
    }
}
