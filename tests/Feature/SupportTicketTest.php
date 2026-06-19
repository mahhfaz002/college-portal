<?php

namespace Tests\Feature;

use App\Models\SupportTicket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCollegeFixtures;
use Tests\TestCase;

class SupportTicketTest extends TestCase
{
    use RefreshDatabase, CreatesCollegeFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();         // settings
        $this->bootCollege();
    }

    public function test_any_staff_can_raise_a_ticket(): void
    {
        $lecturer = $this->userWithRole('lecturer');
        $this->actingAs($lecturer)->post('/support', [
            'subject' => 'Projector not working', 'body' => 'Lab 2 projector is dead.', 'priority' => 'high',
        ])->assertRedirect();

        $this->assertDatabaseHas('support_tickets', ['user_id' => $lecturer->id, 'subject' => 'Projector not working']);
    }

    public function test_proprietor_cannot_raise_ticket(): void
    {
        // Read-only oversight role is blocked from all writes.
        $this->actingAs($this->userWithRole('proprietor'))->post('/support', [
            'subject' => 'x', 'body' => 'y',
        ])->assertForbidden();
    }

    public function test_mis_sees_all_but_lecturer_sees_only_own(): void
    {
        $lecturer = $this->userWithRole('lecturer');
        $bursar = $this->userWithRole('bursar');

        SupportTicket::create(['user_id' => $lecturer->id, 'subject' => 'LecturerProjectorIssue', 'body' => 'a', 'status' => 'open']);
        SupportTicket::create(['user_id' => $bursar->id, 'subject' => 'BursarPrinterIssue', 'body' => 'b', 'status' => 'open']);

        // Lecturer sees only their own.
        $this->actingAs($lecturer)->get('/support')->assertOk()
            ->assertSee('LecturerProjectorIssue')->assertDontSee('BursarPrinterIssue');
        // MIS (handle_tickets) sees both.
        $this->actingAs($this->userWithRole('mis'))->get('/support')->assertOk()
            ->assertSee('LecturerProjectorIssue')->assertSee('BursarPrinterIssue');
    }

    public function test_only_mis_can_resolve(): void
    {
        $ticket = SupportTicket::create(['user_id' => $this->userWithRole('lecturer')->id, 'subject' => 'A', 'body' => 'a', 'status' => 'open']);

        $this->actingAs($this->userWithRole('lecturer'))->put("/support/{$ticket->id}", ['status' => 'resolved'])->assertForbidden();

        $this->actingAs($this->userWithRole('mis'))->put("/support/{$ticket->id}", [
            'status' => 'resolved', 'response' => 'Fixed it.',
        ])->assertRedirect();
        $this->assertSame('resolved', $ticket->fresh()->status);
    }

    public function test_mis_can_reset_password(): void
    {
        $target = $this->userWithRole('lecturer');

        $this->actingAs($this->userWithRole('mis'))->post("/support/reset-password/{$target->id}")->assertRedirect();
        $this->assertTrue($target->fresh()->must_change_password);

        // A non-MIS staff member cannot reset others' passwords.
        $this->actingAs($this->userWithRole('registrar'))->post("/support/reset-password/{$target->id}")->assertForbidden();
    }
}
