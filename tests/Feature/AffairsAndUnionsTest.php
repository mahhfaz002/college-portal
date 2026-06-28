<?php

namespace Tests\Feature;

use App\Models\StudentAffairsCase;
use App\Models\StudentUnion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCollegeFixtures;
use Tests\TestCase;

class AffairsAndUnionsTest extends TestCase
{
    use RefreshDatabase, CreatesCollegeFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->bootCollege();
    }

    public function test_student_search_is_scoped_and_returns_matches(): void
    {
        $s = $this->studentRecord(['full_name' => 'Amina Bello', 'email' => 'amina@x.test']);

        $res = $this->actingAs($this->userWithRole('student_affairs'))
            ->getJson('/affairs/students/search?q=Amina');

        $res->assertOk()->assertJsonFragment(['id' => $s->id, 'name' => 'Amina Bello']);
    }

    public function test_case_logs_multiple_students_without_penalty(): void
    {
        $a = $this->studentRecord(['full_name' => 'Student A', 'email' => 'a@x.test']);
        $b = $this->studentRecord(['full_name' => 'Student B', 'email' => 'b@x.test']);

        $this->actingAs($this->userWithRole('student_affairs'))->post('/affairs/cases', [
            'student_ids'  => [$a->id, $b->id],
            'category'     => 'disciplinary',
            'description'  => 'Noise in the hostel.',
            'recommendation' => 'Verbal warning.',
        ])->assertRedirect();

        $case = StudentAffairsCase::firstOrFail();
        $this->assertEqualsCanonicalizing([$a->id, $b->id], $case->student_ids);
        $this->assertStringContainsString('Student A', $case->student_name);
        $this->assertStringContainsString('Student B', $case->student_name);
    }

    public function test_register_by_student_id_autofills_record(): void
    {
        $s = $this->studentRecord(['full_name' => 'Reg Me', 'email' => 'reg@x.test']);

        $this->actingAs($this->userWithRole('student_affairs'))->post('/affairs/register', [
            'student_id' => $s->id,
            'checklist'  => ['dob_cert' => '1'],
            'notes'      => 'ok',
        ])->assertRedirect();

        $this->assertDatabaseHas('student_affairs_register', ['student_id' => $s->id]);
    }

    public function test_union_lifecycle_create_suspend_delete(): void
    {
        $sa = $this->userWithRole('student_affairs');

        // Create with a president whose tenure runs one year from the start.
        $this->actingAs($sa)->post('/affairs/unions', [
            'name' => 'Student Union Government', 'acronym' => 'SUG',
            'year_established' => 2010, 'members_count' => 500,
            'constituents' => 'All registered students',
            'leaders' => [
                ['name' => 'John Doe', 'department' => 'Health', 'course_of_study' => 'Nursing', 'level' => '300', 'position' => 'President', 'tenure_start' => '2026-01-01'],
            ],
        ])->assertRedirect(route('affairs.unions.index'));

        $union = StudentUnion::with('leaders')->firstOrFail();
        $this->assertSame('SUG', $union->acronym);
        $pres = $union->president();
        $this->assertSame('John Doe', $pres->name);
        $this->assertSame('2027-01-01', $pres->tenure_end->format('Y-m-d')); // +1 year

        // Suspend toggles status.
        $this->actingAs($sa)->post(route('affairs.unions.suspend', $union))->assertRedirect();
        $this->assertTrue($union->fresh()->isSuspended());

        // Delete removes union + cascades leaders.
        $this->actingAs($sa)->delete(route('affairs.unions.destroy', $union))->assertRedirect();
        $this->assertDatabaseMissing('student_unions', ['id' => $union->id]);
        $this->assertDatabaseMissing('student_union_leaders', ['student_union_id' => $union->id]);
    }

    public function test_unions_page_is_student_affairs_only(): void
    {
        $this->actingAs($this->userWithRole('registrar'))->get('/affairs/unions')->assertForbidden();
        $this->actingAs($this->userWithRole('student_affairs'))->get('/affairs/unions')->assertOk();
    }
}
