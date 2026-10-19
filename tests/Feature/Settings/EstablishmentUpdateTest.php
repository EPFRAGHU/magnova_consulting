<?php

namespace Tests\Feature\Settings;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EstablishmentUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_establishment_details_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch(route('establishment.update'), [
            'name' => 'Updated Establishment',
            'establishment_address1' => '12 Main Street',
            'establishment_address2' => 'Second Floor',
            'establishment_city' => 'Bhubaneswar',
            'establishment_district' => 'Khordha',
            'establishment_pin' => '751001',
            'establishment_mobile' => '9876543210',
            'establishment_email' => 'office@example.com',
            'establishment_website' => 'https://example.com',
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect(route('establishment.edit'));

        $this->assertDatabaseHas('tenants', [
            'id' => $user->tenant_id,
            'name' => 'Updated Establishment',
            'establishment_address1' => '12 Main Street',
            'establishment_city' => 'Bhubaneswar',
            'establishment_email' => 'office@example.com',
            'address' => '12 Main Street, Second Floor, Bhubaneswar - 751001, Khordha, Odisha',
            'contact_phone' => '9876543210',
            'contact_email' => 'office@example.com',
        ]);
    }

    public function test_bank_details_can_be_updated_and_ifsc_is_uppercased(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch(route('establishment.update'), [
            'name' => 'Updated Establishment',
            'bank_name' => 'SAMPLE BANK OF INDIA',
            'bank_account_no' => '123456789012',
            'bank_ifsc' => 'smpl0123456',
            'bank_branch' => 'SAMPLE BRANCH, BHUBANESWAR 751000',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('tenants', [
            'id' => $user->tenant_id,
            'bank_name' => 'SAMPLE BANK OF INDIA',
            'bank_account_no' => '123456789012',
            'bank_ifsc' => 'SMPL0123456',
            'bank_branch' => 'SAMPLE BRANCH, BHUBANESWAR 751000',
        ]);
    }

    public function test_invalid_bank_account_and_ifsc_are_rejected(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch(route('establishment.update'), [
            'name' => 'Updated Establishment',
            'bank_account_no' => '12-34 ab',
            'bank_ifsc' => 'BAD',
        ])->assertSessionHasErrors(['bank_account_no', 'bank_ifsc']);
    }

    public function test_saved_bank_details_appear_on_the_invoice(): void
    {
        $user = User::factory()->create();
        $user->tenant->update([
            'bank_name' => 'SAMPLE BANK OF INDIA',
            'bank_account_no' => '123456789012',
            'bank_ifsc' => 'SMPL0123456',
            'bank_branch' => 'SAMPLE BRANCH, BHUBANESWAR 751000',
        ]);
        $client = Client::create(['tenant_id' => $user->tenant_id, 'name' => 'Unit A', 'active' => true]);
        $invoice = Invoice::create([
            'tenant_id' => $user->tenant_id,
            'client_id' => $client->id,
            'invoice_no' => 'T/26-27/1',
            'invoice_date' => '2026-10-01',
            'month' => 9,
            'year' => 2026,
            'rate_per_day' => 500,
            'no_of_persons' => 1,
            'no_of_duties' => 26,
            'base_amount' => 13000,
            'epf_employer' => 0,
            'epf_admin' => 0,
            'edli_cont' => 0,
            'esi_employer' => 0,
            'taxable_value' => 13000,
            'service_charge' => 0,
            'cgst' => 0,
            'sgst' => 0,
            'grand_total' => 13000,
            'status' => 'draft',
        ]);

        $html = view('pdf.invoice', [
            'invoice' => $invoice->load('client', 'lineItems.employee', 'tenant'),
            'dutySummary' => collect(),
            'bonus' => 0,
            'amountInWords' => '',
            'logoDataUri' => null,
        ])->render();

        $this->assertStringContainsString('SAMPLE BANK OF INDIA', $html);
        $this->assertStringContainsString('123456789012', $html);
        $this->assertStringContainsString('SMPL0123456', $html);
        $this->assertStringContainsString('SAMPLE BRANCH, BHUBANESWAR 751000', $html);
    }
}
