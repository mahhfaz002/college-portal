<?php

namespace Tests\Feature;

use App\Models\ClassAttendanceLog;
use App\Models\StaffAttendance;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function teacher(): User
    {
        return User::where('role', 'teacher')->firstOrFail();
    }

    public function test_teacher_can_clock_in_and_out(): void
    {
        $teacher = $this->teacher();

        $this->actingAs($teacher)->post('/clock/in')->assertRedirect();
        $record = StaffAttendance::where('user_id', $teacher->id)->first();
        $this->assertNotNull($record);
        $this->assertSame(now()->toDateString(), $record->work_date->toDateString());
        $this->assertNotNull($record->clock_in);

        $this->actingAs($teacher)->post('/clock/out')->assertRedirect();
        $this->assertNotNull(StaffAttendance::where('user_id', $teacher->id)->first()->clock_out);
    }

    public function test_proprietor_cannot_clock_in(): void
    {
        $p = User::where('role', 'proprietor')->firstOrFail();
        $this->actingAs($p)->post('/clock/in')->assertForbidden();
    }

    public function test_taking_class_attendance_logs_a_class_session(): void
    {
        $teacher = $this->teacher();
        $students = Student::where('class_arm', 'JSS1A')->get();
        $status = $students->mapWithKeys(fn ($s) => [$s->id => 'present'])->all();

        $this->actingAs($teacher)->post('/attendance', [
            'date' => now()->toDateString(),
            'class' => 'JSS1A',
            'status' => $status,
        ])->assertRedirect();

        $this->assertTrue(
            ClassAttendanceLog::where('user_id', $teacher->id)
                ->where('class_arm', 'JSS1A')
                ->whereDate('log_date', now()->toDateString())
                ->exists()
        );
    }

    public function test_principal_sees_attendance_report(): void
    {
        $principal = User::where('role', 'principal')->firstOrFail();
        $this->actingAs($principal)->get('/staff-attendance')->assertOk();
    }

    public function test_non_oversight_cannot_see_report(): void
    {
        $this->actingAs($this->teacher())->get('/staff-attendance')->assertForbidden();
    }
}
