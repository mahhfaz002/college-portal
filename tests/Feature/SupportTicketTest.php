<?php

namespace Tests\Feature;

use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupportTicketTest extends TestCase
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

    public function test_any_staff_can_raise_a_ticket(): void
    {
        $teacher = $this->as('teacher');
        $this->actingAs($teacher)->post('/support', [
            'subject' => 'Projector not working', 'body' => 'Lab 2 projector is dead.', 'priority' => 'high',
        ])->assertRedirect();

        $this->assertDatabaseHas('support_tickets', ['user_id' => $teacher->id, 'subject' => 'Projector not working']);
    }

    public function test_proprietor_cannot_raise_ticket(): void
    {
        // Read-only role is blocked from all writes.
        $this->actingAs($this->as('proprietor'))->post('/support', [
            'subject' => 'x', 'body' => 'y',
        ])->assertForbidden();
    }

    public function test_ict_sees_all_but_teacher_sees_only_own(): void
    {
        $teacher = $this->as('teacher');
        $bursar = $this->as('accountant');

        SupportTicket::create(['user_id' => $teacher->id, 'subject' => 'TeacherProjectorIssue', 'body' => 'a', 'status' => 'open']);
        SupportTicket::create(['user_id' => $bursar->id, 'subject' => 'BursarPrinterIssue', 'body' => 'b', 'status' => 'open']);

        // Teacher sees only their own.
        $this->actingAs($teacher)->get('/support')->assertOk()
            ->assertSee('TeacherProjectorIssue')->assertDontSee('BursarPrinterIssue');
        // ICT sees both.
        $this->actingAs($this->as('ict'))->get('/support')->assertOk()
            ->assertSee('TeacherProjectorIssue')->assertSee('BursarPrinterIssue');
    }

    public function test_only_ict_can_resolve(): void
    {
        $ticket = SupportTicket::create(['user_id' => $this->as('teacher')->id, 'subject' => 'A', 'body' => 'a', 'status' => 'open']);

        $this->actingAs($this->as('teacher'))->put("/support/{$ticket->id}", ['status' => 'resolved'])->assertForbidden();

        $this->actingAs($this->as('ict'))->put("/support/{$ticket->id}", [
            'status' => 'resolved', 'response' => 'Fixed it.',
        ])->assertRedirect();
        $this->assertSame('resolved', $ticket->fresh()->status);
    }

    public function test_ict_can_reset_password(): void
    {
        $target = $this->as('teacher');

        $this->actingAs($this->as('ict'))->post("/support/reset-password/{$target->id}")->assertRedirect();
        $this->assertTrue($target->fresh()->must_change_password);

        // A non-ICT staff member cannot reset others' passwords.
        $this->actingAs($this->as('principal'))->post("/support/reset-password/{$target->id}")->assertForbidden();
    }
}
