<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Invoice;
use App\Models\Program;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCollegeFixtures;
use Tests\TestCase;

/**
 * A bursar payment order must hit ONLY the targeted cohort — never spill to a
 * different programme, a different level of the same programme, or another
 * department.
 */
class FeeOrderTargetingTest extends TestCase
{
    use RefreshDatabase, CreatesCollegeFixtures;

    private function student(Program $p, string $level, string $name): Student
    {
        return Student::create([
            'full_name' => $name, 'email' => str()->random(8).'@gmail.com',
            'admission_number' => 'ADM/'.str()->random(4), 'class_arm' => $p->name,
            'parent_phone' => '080', 'fees_balance' => 0,
            'college_id' => $this->college->id, 'department_id' => $p->department_id,
            'program_id' => $p->id, 'level' => $level, 'registration_status' => 'registration_paid',
        ]);
    }

    public function test_program_and_level_order_does_not_spill(): void
    {
        $this->seed();
        $this->bootCollege(['domain' => 'localhost']);

        $pharmDept = Department::create(['name' => 'Pharmacy Tech', 'acronym' => 'PT', 'section' => 'UG']);
        $otherDept = Department::create(['name' => 'Community Health', 'acronym' => 'CH', 'section' => 'UG']);
        $diploma = Program::create(['name' => 'PharmTech Diploma', 'acronym' => 'PTD', 'department_id' => $pharmDept->id]);
        $cert    = Program::create(['name' => 'PharmTech Cert', 'acronym' => 'PTC', 'department_id' => $pharmDept->id]);
        $chew    = Program::create(['name' => 'CHEW', 'acronym' => 'CH', 'department_id' => $otherDept->id]);

        $target   = $this->student($diploma, '300', 'Target Diploma 300');   // SHOULD be charged
        $sameProgDiffLvl = $this->student($diploma, '100', 'Diploma 100');    // must NOT
        $certSameDept    = $this->student($cert, '300', 'Cert 300');          // must NOT
        $otherDeptStu    = $this->student($chew, '300', 'CHEW 300');          // must NOT

        $bursar = $this->userWithRole('bursar');
        $this->actingAs($bursar)->post(route('fees.orders.store'), [
            'title' => 'PharmTech Diploma 300 Levy', 'amount' => 5000,
            'mode' => 'filter', 'department_id' => $pharmDept->id,
            'program_id' => $diploma->id, 'level' => '300',
        ])->assertRedirect();

        $charged = Invoice::where('purpose', 'fee')->pluck('student_id')->all();

        $this->assertContains($target->id, $charged);
        $this->assertNotContains($sameProgDiffLvl->id, $charged, 'Different level must not be charged');
        $this->assertNotContains($certSameDept->id, $charged, 'Different programme (cert) must not be charged');
        $this->assertNotContains($otherDeptStu->id, $charged, 'Other department must not be charged');
        $this->assertCount(1, $charged);
    }

    public function test_targeting_a_programme_without_a_level_is_rejected(): void
    {
        $this->seed();
        $this->bootCollege(['domain' => 'localhost']);
        $dept = Department::create(['name' => 'Pharmacy Tech', 'acronym' => 'PT', 'section' => 'UG']);
        $prog = Program::create(['name' => 'PharmTech Diploma', 'acronym' => 'PTD', 'department_id' => $dept->id]);

        $this->actingAs($this->userWithRole('bursar'))
            ->post(route('fees.orders.store'), [
                'title' => 'Levy', 'amount' => 5000, 'mode' => 'filter',
                'department_id' => $dept->id, 'program_id' => $prog->id, // no level
            ])
            ->assertSessionHasErrors('level');

        $this->assertSame(0, Invoice::where('purpose', 'fee')->count());
    }
}
