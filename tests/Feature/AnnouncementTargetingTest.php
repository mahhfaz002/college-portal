<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnouncementTargetingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function studentInClass(string $class): User
    {
        Student::create([
            'full_name' => 'Pupil '.$class, 'admission_number' => 'A/'.$class,
            'class_arm' => $class, 'parent_phone' => '0', 'fees_balance' => 0,
            'email' => strtolower($class).'@pupil.local',
        ]);
        return User::create([
            'name' => 'Pupil', 'email' => strtolower($class).'@pupil.local',
            'password' => bcrypt('password'), 'role' => 'student', 'must_change_password' => false,
        ]);
    }

    public function test_principal_can_post_class_targeted_announcement(): void
    {
        $principal = User::where('role', 'principal')->firstOrFail();
        $this->actingAs($principal)->post('/announcements', [
            'title' => 'JSS1A Outing', 'body' => 'Bring consent forms.',
            'audience' => 'class', 'target_class' => 'JSS1A',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('announcements', ['title' => 'JSS1A Outing', 'target_class' => 'JSS1A']);
    }

    public function test_class_announcement_visible_only_to_that_class(): void
    {
        $principal = User::where('role', 'principal')->firstOrFail();
        Announcement::create(['user_id' => $principal->id, 'title' => 'JSS1A Notice', 'body' => 'x', 'audience' => 'class', 'target_class' => 'JSS1A']);

        $jss1a = $this->studentInClass('JSS1A');
        $jss2a = $this->studentInClass('JSS2A');

        $this->actingAs($jss1a)->get('/announcements')->assertOk()->assertSee('JSS1A Notice');
        $this->actingAs($jss2a)->get('/announcements')->assertOk()->assertDontSee('JSS1A Notice');
    }

    public function test_students_only_announcement_hidden_from_staff_only(): void
    {
        $principal = User::where('role', 'principal')->firstOrFail();
        Announcement::create(['user_id' => $principal->id, 'title' => 'StudentsOnlyNotice', 'body' => 'x', 'audience' => 'students']);
        Announcement::create(['user_id' => $principal->id, 'title' => 'StaffOnlyNotice', 'body' => 'y', 'audience' => 'staff']);

        $student = $this->studentInClass('JSS1A');
        $this->actingAs($student)->get('/announcements')->assertOk()
            ->assertSee('StudentsOnlyNotice')->assertDontSee('StaffOnlyNotice');

        $teacher = User::where('role', 'teacher')->firstOrFail();
        $this->actingAs($teacher)->get('/announcements')->assertOk()
            ->assertSee('StaffOnlyNotice')->assertDontSee('StudentsOnlyNotice');
    }
}
