<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Department;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCollegeFixtures;
use Tests\TestCase;

class AnnouncementTargetingTest extends TestCase
{
    use RefreshDatabase, CreatesCollegeFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->bootCollege();
    }

    /** A student login linked (by email) to a record in the given class arm. */
    private function studentInClass(string $class): User
    {
        $email = strtolower($class).'@pupil.test';
        $this->studentRecord(['full_name' => 'Pupil '.$class, 'admission_number' => 'A/'.$class, 'class_arm' => $class, 'email' => $email]);

        return $this->userWithRole('student', ['name' => 'Pupil', 'email' => $email]);
    }

    public function test_mis_can_post_class_targeted_announcement(): void
    {
        // MIS authors announcements (proprietor is read-only oversight).
        $this->actingAs($this->userWithRole('mis'))->post('/announcements', [
            'title' => 'UG1A Outing', 'body' => 'Bring consent forms.',
            'audience' => 'class', 'target_class' => 'UG1A',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('announcements', ['title' => 'UG1A Outing', 'target_class' => 'UG1A']);
    }

    public function test_class_announcement_visible_only_to_that_class(): void
    {
        $author = $this->userWithRole('mis');
        Announcement::create(['user_id' => $author->id, 'title' => 'UG1A Notice', 'body' => 'x', 'audience' => 'class', 'target_class' => 'UG1A']);

        $ug1a = $this->studentInClass('UG1A');
        $ug2a = $this->studentInClass('UG2A');

        $this->actingAs($ug1a)->get('/announcements')->assertOk()->assertSee('UG1A Notice');
        $this->actingAs($ug2a)->get('/announcements')->assertOk()->assertDontSee('UG1A Notice');
    }

    public function test_students_only_announcement_hidden_from_staff_only(): void
    {
        $author = $this->userWithRole('mis');
        Announcement::create(['user_id' => $author->id, 'title' => 'StudentsOnlyNotice', 'body' => 'x', 'audience' => 'students']);
        Announcement::create(['user_id' => $author->id, 'title' => 'StaffOnlyNotice', 'body' => 'y', 'audience' => 'staff']);

        $student = $this->studentInClass('UG1A');
        $this->actingAs($student)->get('/announcements')->assertOk()
            ->assertSee('StudentsOnlyNotice')->assertDontSee('StaffOnlyNotice');

        $lecturer = $this->userWithRole('lecturer');
        $this->actingAs($lecturer)->get('/announcements')->assertOk()
            ->assertSee('StaffOnlyNotice')->assertDontSee('StudentsOnlyNotice');
    }

    public function test_student_affairs_can_target_students_by_department(): void
    {
        $deptA = Department::create(['name' => 'Nursing', 'acronym' => 'N', 'section' => 'UG']);
        $deptB = Department::create(['name' => 'Public Health', 'acronym' => 'P', 'section' => 'UG']);

        // Student Affairs posts (its audience is always students); narrowed to dept A.
        $this->actingAs($this->userWithRole('student_affairs'))->post('/announcements', [
            'title' => 'Nursing meeting', 'body' => 'Today at 2pm',
            'target_department_id' => $deptA->id,
        ])->assertSessionHasNoErrors();

        $ann = Announcement::firstOrFail();
        $this->assertSame('students', $ann->audience);
        $this->assertSame($deptA->id, (int) $ann->target_department_id);

        $inA = $this->studentRecord(['email' => 'na@x.test', 'department_id' => $deptA->id]);
        $inB = $this->studentRecord(['email' => 'nb@x.test', 'department_id' => $deptB->id]);

        $this->assertTrue(Announcement::visibleTo('student', null, $inA)->where('id', $ann->id)->exists());
        $this->assertFalse(Announcement::visibleTo('student', null, $inB)->where('id', $ann->id)->exists());
    }

    public function test_student_affairs_cannot_delete_another_users_announcement(): void
    {
        $otherAuthor = $this->userWithRole('mis');
        $foreign = Announcement::create(['college_id' => $this->college->id, 'user_id' => $otherAuthor->id, 'title' => 'Not mine', 'body' => 'x', 'audience' => 'students', 'is_published' => true]);

        $this->actingAs($this->userWithRole('student_affairs'))
            ->delete(route('announcements.destroy', $foreign))
            ->assertForbidden();

        $this->assertDatabaseHas('announcements', ['id' => $foreign->id]);
    }
}
