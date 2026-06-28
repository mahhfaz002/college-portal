<?php

namespace Tests\Feature;

use App\Models\ChangeOfCourseRequest;
use App\Models\Department;
use App\Models\Program;
use App\Support\Notifications;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCollegeFixtures;
use Tests\TestCase;

/**
 * The nav bell counts only NEW notifications; opening the notifications page
 * marks them read and clears the badge to zero.
 */
class NotificationsReadStateTest extends TestCase
{
    use RefreshDatabase, CreatesCollegeFixtures;

    public function test_bell_counts_new_items_and_clears_on_open(): void
    {
        $this->seed();
        $this->bootCollege();

        $dept    = Department::create(['name' => 'Health', 'acronym' => 'H', 'section' => 'UG']);
        $from    = Program::create(['name' => 'Nursing', 'acronym' => 'N', 'department_id' => $dept->id]);
        $to      = Program::create(['name' => 'Public Health', 'acronym' => 'PH', 'department_id' => $dept->id]);
        $student = $this->studentRecord(['program_id' => $from->id, 'department_id' => $dept->id]);

        // A request waiting on the Registrar generates a registrar notification.
        ChangeOfCourseRequest::create([
            'college_id' => $this->college->id, 'student_id' => $student->id,
            'current_program_id' => $from->id, 'requested_program_id' => $to->id,
            'reason' => 'x', 'status' => 'registrar_review',
        ]);

        $registrar = $this->userWithRole('registrar');

        // Fresh registrar (never opened notifications) sees a positive badge.
        $this->assertGreaterThanOrEqual(1, Notifications::forUser($registrar->fresh())['count']);

        // Opening the page stamps last-read…
        $this->actingAs($registrar)->get(route('notifications.index'))->assertOk();

        // …and the badge clears to zero (nothing new since).
        $this->assertSame(0, Notifications::forUser($registrar->fresh())['count']);
    }
}
