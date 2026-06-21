<?php

namespace Tests\Feature;

use App\Models\InventoryItem;
use App\Models\StudentDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesCollegeFixtures;
use Tests\TestCase;

class SecurityFixesTest extends TestCase
{
    use RefreshDatabase, CreatesCollegeFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->bootCollege();
    }

    /** M1 — the open Breeze self-registration route is gone. */
    public function test_open_register_route_is_removed(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', [])->assertNotFound(); // route no longer exists
    }

    /** M4 — verification is enforced; an unverified account is gated. */
    public function test_unverified_user_is_blocked_but_verified_passes(): void
    {
        $unverified = User::factory()->mis()->unverified()->create();
        $this->actingAs($unverified)->get('/dashboard')->assertRedirect(route('verification.notice'));

        $verified = $this->userWithRole('mis'); // factory verifies by default
        $this->actingAs($verified)->get('/dashboard')->assertOk();
    }

    /** M2 — mass assignment can't push inventory into another college. */
    public function test_inventory_cannot_be_mass_assigned_to_another_college(): void
    {
        $this->actingAs($this->userWithRole('mis'))->post('/inventory', [
            'item_name'  => 'Microscope', 'category' => 'Lab', 'quantity' => 5,
            'status'     => 'available', 'location' => 'Lab A',
            'college_id' => 99999,   // spoof attempt — must be ignored
        ])->assertRedirect();

        $item = InventoryItem::withoutGlobalScopes()->firstWhere('item_name', 'Microscope');
        $this->assertNotNull($item);
        $this->assertSame($this->college->id, (int) $item->college_id);
    }

    /** H1 — registration documents are streamed only to authorised users. */
    public function test_documents_are_access_controlled(): void
    {
        // Fake whichever disk the controller is configured to use (DOCUMENTS_DISK):
        // 'local' in dev, 'public'/'s3' elsewhere. Hardcoding 'local' made this
        // test pass locally but 404 in CI (where .env.example sets it to 'public').
        $disk = config('filesystems.documents', 'local');
        Storage::fake($disk);
        Storage::disk($disk)->put('documents/registration/test.pdf', 'PDFDATA');

        $student = $this->studentRecord(['email' => 'doc.owner@example.test', 'department_id' => null]);
        $ownerUser = $this->userWithRole('student', ['email' => 'doc.owner@example.test']);

        $doc = StudentDocument::create([
            'college_id' => $this->college->id, 'student_id' => $student->id,
            'type' => 'ssce', 'label' => 'SSCE', 'path' => 'documents/registration/test.pdf',
            'original_name' => 'ssce.pdf',
        ]);

        // Owner can fetch it.
        $this->actingAs($ownerUser)->get(route('documents.show', $doc))->assertOk();
        // A different student cannot.
        $other = $this->userWithRole('student', ['email' => 'other@example.test']);
        $this->actingAs($other)->get(route('documents.show', $doc))->assertForbidden();
        // A lecturer (no view_students) cannot.
        $this->actingAs($this->userWithRole('lecturer'))->get(route('documents.show', $doc))->assertForbidden();
        // The registrar can.
        $this->actingAs($this->userWithRole('registrar'))->get(route('documents.show', $doc))->assertOk();
    }
}
