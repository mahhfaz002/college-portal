<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Program;
use App\Models\Student;
use App\Models\StudentDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCollegeFixtures;
use Tests\TestCase;

/**
 * A submitted registration goes ONLY to the HOD of the student's department —
 * never to another department's HOD — for review, document download and
 * approval.
 */
class HodRegistrationIsolationTest extends TestCase
{
    use RefreshDatabase, CreatesCollegeFixtures;

    private Department $deptA;
    private Department $deptB;
    private Student $stuA;
    private Student $stuB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->bootCollege(['domain' => 'localhost']);

        $this->deptA = Department::create(['name' => 'Pharmacy Tech', 'acronym' => 'PT', 'section' => 'UG']);
        $this->deptB = Department::create(['name' => 'Community Health', 'acronym' => 'CH', 'section' => 'UG']);
        $progA = Program::create(['name' => 'PharmTech', 'acronym' => 'PT', 'department_id' => $this->deptA->id]);
        $progB = Program::create(['name' => 'CHEW', 'acronym' => 'CH', 'department_id' => $this->deptB->id]);

        $this->stuA = $this->pendingStudent($progA, $this->deptA, 'Aisha PT');
        $this->stuB = $this->pendingStudent($progB, $this->deptB, 'Bala CH');
    }

    private function pendingStudent(Program $p, Department $d, string $name): Student
    {
        return Student::create([
            'full_name' => $name, 'email' => str()->random(6).'@gmail.com',
            'admission_number' => 'ADM/'.str()->random(4), 'class_arm' => $p->name, 'parent_phone' => '080',
            'fees_balance' => 0, 'college_id' => $this->college->id, 'department_id' => $d->id,
            'program_id' => $p->id, 'level' => '100', 'registration_status' => 'pending_hod',
        ]);
    }

    private function hodOf(Department $d)
    {
        return $this->userWithRole('hod', ['department_id' => $d->id]);
    }

    public function test_hod_sees_only_their_departments_pending_registrations(): void
    {
        $this->actingAs($this->hodOf($this->deptA))
            ->get(route('hod.registrations'))
            ->assertOk()
            ->assertSee('Aisha PT')
            ->assertDontSee('Bala CH');
    }

    public function test_hod_cannot_approve_another_departments_student(): void
    {
        $this->actingAs($this->hodOf($this->deptA))
            ->post(route('hod.registrations.approve', $this->stuB))
            ->assertForbidden();

        $this->assertSame('pending_hod', $this->stuB->fresh()->registration_status);
    }

    public function test_hod_approval_marks_their_student_registered(): void
    {
        $this->actingAs($this->hodOf($this->deptA))
            ->post(route('hod.registrations.approve', $this->stuA))
            ->assertRedirect();

        $this->assertSame('registered', $this->stuA->fresh()->registration_status);
    }

    public function test_hod_cannot_download_another_departments_document(): void
    {
        $doc = StudentDocument::create([
            'student_id' => $this->stuB->id, 'college_id' => $this->college->id,
            'type' => 'ssce', 'label' => 'SSCE', 'path' => 'documents/registration/x.pdf',
            'original_name' => 'x.pdf',
        ]);

        $this->actingAs($this->hodOf($this->deptA))
            ->get(route('documents.show', $doc))
            ->assertForbidden();
    }
}
