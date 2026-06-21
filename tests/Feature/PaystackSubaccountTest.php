<?php

namespace Tests\Feature;

use App\Models\College;
use App\Models\Invoice;
use App\Services\PaystackService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The marketplace split hinges on a college's Paystack subaccount CODE being
 * stored locally and sent at payment time. These tests pin the self-healing
 * behaviour that keeps the code linked and the split firing.
 */
class PaystackSubaccountTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.paystack.secret_key' => 'sk_test_master']);
    }

    private function college(array $attrs = []): College
    {
        return College::create(array_merge([
            'name'    => 'Albaz College',
            'acronym' => 'ALB',
            'email'   => 'info@albaz.edu.ng',
            'is_active' => true,
            'settlement_bank'           => '058',
            'settlement_account_number' => '0001234567',
            'commission_percentage'     => 5,
        ], $attrs));
    }

    /** Build a Paystack-style fake keyed by method + path. */
    private function fakePaystack(array $existingSubaccounts = []): void
    {
        Http::fake(function ($request) use ($existingSubaccounts) {
            $url = $request->url();
            $method = $request->method();

            if (str_contains($url, '/subaccount') && $method === 'GET' && !preg_match('#/subaccount/ACCT#', $url)) {
                return Http::response(['status' => true, 'data' => $existingSubaccounts]);
            }
            if (str_contains($url, '/subaccount') && $method === 'POST') {
                return Http::response(['status' => true, 'data' => [
                    'subaccount_code' => 'ACCT_created', 'business_name' => 'Albaz College',
                    'account_name' => 'Albaz Ltd', 'active' => true,
                ]]);
            }
            if (preg_match('#/subaccount/(ACCT[\w]+)#', $url, $m)) {
                return Http::response(['status' => true, 'data' => [
                    'subaccount_code' => $m[1], 'business_name' => 'Albaz College',
                    'account_number' => '0001234567', 'settlement_bank' => '058',
                    'account_name' => 'Albaz Ltd', 'active' => true, 'percentage_charge' => 5,
                ]]);
            }
            if (str_contains($url, '/transaction/initialize')) {
                return Http::response(['status' => true, 'data' => [
                    'authorization_url' => 'https://checkout.paystack.com/abc', 'reference' => 'ref_x',
                ]]);
            }
            return Http::response(['status' => true, 'data' => []]);
        });
    }

    public function test_create_persists_the_subaccount_code(): void
    {
        $this->fakePaystack();          // no existing subaccounts → POST creates
        $college = $this->college();

        app(PaystackService::class)->createOrUpdateSubaccount($college, [
            'settlement_bank' => '058', 'account_number' => '0001234567', 'percentage_charge' => 5,
        ]);

        $this->assertSame('ACCT_created', $college->fresh()->paystack_subaccount_code);
        $this->assertSame('active', $college->fresh()->paystack_subaccount_status);
    }

    public function test_create_adopts_an_existing_subaccount_instead_of_duplicating(): void
    {
        // Paystack already has a subaccount for this exact account → must ADOPT it
        // (PUT), never POST a duplicate.
        $this->fakePaystack([[
            'subaccount_code' => 'ACCT_existing', 'account_number' => '0001234567',
            'settlement_bank' => '058', 'business_name' => 'Albaz College', 'active' => true,
        ]]);
        $college = $this->college();

        app(PaystackService::class)->createOrUpdateSubaccount($college, [
            'settlement_bank' => '058', 'account_number' => '0001234567', 'percentage_charge' => 5,
        ]);

        $this->assertSame('ACCT_existing', $college->fresh()->paystack_subaccount_code);
        Http::assertNotSent(fn ($r) => $r->method() === 'POST' && str_ends_with($r->url(), '/subaccount'));
    }

    public function test_sync_recovers_a_lost_code_by_settlement_account(): void
    {
        $this->fakePaystack([[
            'subaccount_code' => 'ACCT_recovered', 'account_number' => '0001234567',
            'settlement_bank' => '058', 'business_name' => 'Albaz College', 'active' => true,
        ]]);
        $college = $this->college(); // settlement details, but NO code stored

        $data = app(PaystackService::class)->fetchSubaccount($college);

        $this->assertNotNull($data);
        $this->assertSame('ACCT_recovered', $college->fresh()->paystack_subaccount_code);
    }

    public function test_initialize_sends_the_subaccount_split_param(): void
    {
        $this->fakePaystack();
        $college = $this->college(['paystack_subaccount_code' => 'ACCT_live', 'paystack_subaccount_status' => 'active']);
        $invoice = Invoice::create([
            'college_id' => $college->id, 'purpose' => 'fee', 'description' => 'Levy',
            'amount' => 5000, 'payer_email' => 'pay@gmail.com', 'status' => 'pending',
            'reference' => PaystackService::reference('TST', $college->id),
        ]);

        app(PaystackService::class)->initialize($invoice, 'https://app.test/callback');

        Http::assertSent(fn ($r) => str_contains($r->url(), '/transaction/initialize')
            && ($r['subaccount'] ?? null) === 'ACCT_live');
    }

    public function test_initialize_self_heals_a_missing_code_then_splits(): void
    {
        // College has settlement details but no code; Paystack already has the
        // subaccount → initialize must reconcile and STILL send the split param.
        $this->fakePaystack([[
            'subaccount_code' => 'ACCT_existing', 'account_number' => '0001234567',
            'settlement_bank' => '058', 'business_name' => 'Albaz College', 'active' => true,
        ]]);
        $college = $this->college();
        $invoice = Invoice::create([
            'college_id' => $college->id, 'purpose' => 'fee', 'description' => 'Levy',
            'amount' => 5000, 'payer_email' => 'pay@gmail.com', 'status' => 'pending',
            'reference' => PaystackService::reference('TST', $college->id),
        ]);

        app(PaystackService::class)->initialize($invoice, 'https://app.test/callback');

        $this->assertSame('ACCT_existing', $college->fresh()->paystack_subaccount_code);
        Http::assertSent(fn ($r) => str_contains($r->url(), '/transaction/initialize')
            && ($r['subaccount'] ?? null) === 'ACCT_existing');
    }

    public function test_reconcile_command_links_colleges_with_settlement_details(): void
    {
        $this->fakePaystack([[
            'subaccount_code' => 'ACCT_existing', 'account_number' => '0001234567',
            'settlement_bank' => '058', 'business_name' => 'Albaz College', 'active' => true,
        ]]);
        $college = $this->college();

        $this->artisan('paystack:reconcile-subaccounts')->assertExitCode(0);

        $this->assertSame('ACCT_existing', $college->fresh()->paystack_subaccount_code);
    }
}
