<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCollegeFixtures;
use Tests\TestCase;

class SignatureSaveTest extends TestCase
{
    use RefreshDatabase, CreatesCollegeFixtures;

    /** @dataProvider signatureRoles */
    public function test_role_can_save_drawn_signature_and_page_rerenders(string $role): void
    {
        $this->seed();
        $this->bootCollege();

        $user = $this->userWithRole($role);
        $png  = 'data:image/png;base64,'.base64_encode("\x89PNG\r\n\x1a\nfake-png-bytes");

        // Save → redirects back (never a 500).
        $this->actingAs($user)->post('/profile/signature', ['signature_data' => $png])
            ->assertStatus(302)
            ->assertSessionHas('success');

        $user = $user->fresh();
        $this->assertNotNull($user->signature_path);

        // Following back to the edit page renders cleanly (catches render-side 500).
        $this->actingAs($user)->get(route('signature.edit'))->assertOk();

        // The signature image route streams for the owner.
        $this->actingAs($user)->get(route('signature.show', $user))->assertOk();
    }

    public static function signatureRoles(): array
    {
        return [['registrar'], ['provost']];
    }

    public function test_empty_submit_is_a_friendly_error_not_a_500(): void
    {
        $this->seed();
        $this->bootCollege();

        $this->actingAs($this->userWithRole('registrar'))
            ->post('/profile/signature', [])
            ->assertStatus(302)
            ->assertSessionHas('error');
    }

    public function test_missing_column_is_a_friendly_error_not_a_500(): void
    {
        $this->seed();
        $this->bootCollege();

        // Simulate a database where the signature migration never landed.
        \Illuminate\Support\Facades\Schema::table('users', function ($t) {
            $t->dropColumn('signature_path');
        });

        $png = 'data:image/png;base64,'.base64_encode('x');
        $this->actingAs($this->userWithRole('registrar'))
            ->post('/profile/signature', ['signature_data' => $png])
            ->assertStatus(302)
            ->assertSessionHas('error');
    }
}
