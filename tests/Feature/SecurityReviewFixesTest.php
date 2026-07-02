<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\User;
use App\Services\PaystackService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesCollegeFixtures;
use Tests\TestCase;

/**
 * Regression tests for the July-2026 security review fixes:
 *  1. Public pay routes must not authenticate the caller (account-takeover IDOR).
 *  2. E-signature images are readable only by the owner + document-rendering
 *     leadership — never students/ordinary staff (document-forgery vector).
 *  3. A staff manager (registrar) cannot edit or delete leadership accounts.
 *  4. Email is the unique, case-normalised account identity ("username").
 */
class SecurityReviewFixesTest extends TestCase
{
    use RefreshDatabase, CreatesCollegeFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        // Guests resolve the tenant by host; the HTTP test host is 'localhost'.
        $this->bootCollege(['domain' => 'localhost']);
    }

    // ---- 1. Payment account-takeover IDOR ---------------------------------

    public function test_public_pay_route_does_not_log_in_a_guest_for_a_paid_invoice(): void
    {
        $victim  = $this->userWithRole('student', ['email' => 'victim@example.test']);
        $invoice = $this->paidPlatformInvoice($victim);

        // The enumerable, public initialize route must not establish a session.
        $this->get(route('payments.initialize', $invoice))->assertRedirect(route('login'));
        $this->assertGuest();

        // Same for the checkout alias.
        $this->get(route('payments.checkout', $invoice))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_public_pay_route_does_not_switch_an_authenticated_user_to_the_payer(): void
    {
        $victim   = $this->userWithRole('student', ['email' => 'victim2@example.test']);
        $attacker = $this->userWithRole('student', ['email' => 'attacker@example.test']);
        $invoice  = $this->paidPlatformInvoice($victim);

        $this->actingAs($attacker)->get(route('payments.initialize', $invoice))->assertRedirect();

        // Still the attacker's own session — never re-bound to the invoice owner.
        $this->assertSame($attacker->id, auth()->id());
    }

    private function paidPlatformInvoice(User $owner): Invoice
    {
        return Invoice::create([
            'college_id'  => $this->college->id,
            'user_id'     => $owner->id,
            'purpose'     => 'platform_registration',
            'description' => 'Platform registration fee',
            'amount'      => 5000,
            'payer_email' => $owner->email,
            'status'      => 'paid',
            'paid_at'     => now(),
            'reference'   => PaystackService::reference('PLT', $this->college->id),
        ]);
    }

    // ---- 2. E-signature exposure ------------------------------------------

    public function test_signature_image_is_restricted_to_owner_and_leadership(): void
    {
        $disk = config('filesystems.documents', 'local');
        Storage::fake($disk);
        Storage::disk($disk)->put('signatures/reg.png', 'PNGDATA');

        $registrar = $this->userWithRole('registrar');
        $registrar->forceFill(['signature_path' => 'signatures/reg.png'])->save();

        // Students and ordinary staff must NOT be able to fetch the signature.
        $this->actingAs($this->userWithRole('student'))
            ->get(route('signature.show', $registrar))->assertForbidden();
        $this->actingAs($this->userWithRole('lecturer'))
            ->get(route('signature.show', $registrar))->assertForbidden();

        // The owner, and document-rendering leadership, can.
        $this->actingAs($registrar)->get(route('signature.show', $registrar))->assertOk();
        $this->actingAs($this->userWithRole('provost'))
            ->get(route('signature.show', $registrar))->assertOk();
    }

    // ---- 3. Staff-manager cannot touch leadership accounts ----------------

    public function test_registrar_cannot_delete_leadership_account(): void
    {
        $registrar = $this->userWithRole('registrar');
        $provost   = $this->userWithRole('provost');

        $this->actingAs($registrar)->delete(route('staff.destroy', $provost))->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $provost->id]);
    }

    public function test_registrar_cannot_edit_leadership_account(): void
    {
        $registrar = $this->userWithRole('registrar');
        $bursar    = $this->userWithRole('bursar');

        $this->actingAs($registrar)->get(route('staff.edit', $bursar))->assertForbidden();
        $this->actingAs($registrar)->put(route('staff.update', $bursar), [
            'first_name' => 'X', 'surname' => 'Y', 'role' => 'lecturer',
            'email' => 'x@example.test',
        ])->assertForbidden();

        // Untouched.
        $this->assertSame('bursar', $bursar->fresh()->role);
    }

    public function test_registrar_can_still_delete_ordinary_staff(): void
    {
        $registrar = $this->userWithRole('registrar');
        $lecturer  = $this->userWithRole('lecturer');

        $this->actingAs($registrar)->delete(route('staff.destroy', $lecturer))->assertRedirect();
        $this->assertDatabaseMissing('users', ['id' => $lecturer->id]);
    }

    // ---- 4. Email as the unique, normalised identity ----------------------

    public function test_email_is_stored_trimmed_and_lowercased(): void
    {
        $user = User::factory()->create(['email' => '  MixedCase@Example.TEST  ']);

        $this->assertSame('mixedcase@example.test', $user->fresh()->email);
    }

    public function test_login_matches_email_case_insensitively(): void
    {
        User::factory()->create(['email' => 'jane@example.test']);

        $this->post('/login', ['email' => 'JANE@Example.TEST', 'password' => 'password'])
            ->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticated();
    }
}
