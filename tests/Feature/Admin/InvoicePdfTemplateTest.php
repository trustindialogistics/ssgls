<?php

use App\Models\Address;
use App\Models\CustomField;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\get;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);
    Artisan::call('db:seed', ['--class' => 'DemoSeeder', '--force' => true]);

    $user = User::find(1);

    $this->withHeaders([
        'company' => $user->companies()->first()->id,
    ]);

    $this->actingAs($user);

    Sanctum::actingAs($user, ['*']);
});

test('lr receipt preview includes consignor and consignee phone numbers', function () {
    $user = User::find(1);
    $company = $user->companies()->first();

    $customer = Customer::factory()->create([
        'company_id' => $company->id,
        'phone' => '8080808080',
    ]);

    $invoice = Invoice::factory()
        ->hasItems(1)
        ->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'template_name' => Invoice::TEMPLATE_LR_RECEIPT,
            'unique_hash' => 'lr-phone-preview',
        ]);

    $consignorPhoneField = CustomField::factory()->create([
        'company_id' => $company->id,
        'model_type' => 'Invoice',
        'name' => 'consignor_phone_no',
        'label' => 'consignor_phone_no',
        'slug' => 'consignor_phone_no',
        'type' => 'Input',
    ]);

    $invoice->fields()->create([
        'company_id' => $company->id,
        'custom_field_id' => $consignorPhoneField->id,
        'type' => 'Input',
        'string_answer' => '9090909090',
    ]);

    $response = get("/invoices/pdf/{$invoice->unique_hash}?preview=1&template_name=".Invoice::TEMPLATE_LR_RECEIPT);

    $response->assertOk();

    expect($response->getContent())
        ->toContain('<span class="label">Phone No.:</span> 9090909090')
        ->toContain('<span class="label">Phone No.:</span> 8080808080');
});

test('office invoice preview uses billing address name for party name', function () {
    $user = User::find(1);
    $company = $user->companies()->first();

    $customer = Customer::factory()->create([
        'company_id' => $company->id,
        'name' => 'Display Name Customer',
    ]);

    Address::factory()->create([
        'company_id' => $company->id,
        'customer_id' => $customer->id,
        'type' => Address::BILLING_TYPE,
        'name' => 'Billing Address Name Private Ltd',
        'address_street_1' => 'Billing Street One',
        'address_street_2' => 'Billing Street Two',
        'city' => 'Vapi',
        'state' => 'Gujarat',
        'zip' => '396191',
    ]);

    $invoice = Invoice::factory()
        ->hasItems(1)
        ->create([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'template_name' => Invoice::TEMPLATE_OFFICE_INVOICE,
            'unique_hash' => 'office-billing-preview',
        ]);

    $response = get("/invoices/pdf/{$invoice->unique_hash}?preview=1&template_name=".Invoice::TEMPLATE_OFFICE_INVOICE);

    $response->assertOk();

    expect($response->getContent())
        ->toContain('<div class="party-display-name">Billing Address Name Private Ltd</div>')
        ->toContain('Billing Street One')
        ->toContain('Billing Street Two')
        ->not->toContain('<div class="party-display-name">Display Name Customer</div>');
});

test('lorry receipt preview fills section c and e rupees boxes from current field labels', function () {
    $user = User::find(1);
    $company = $user->companies()->first();

    $invoice = Invoice::factory()
        ->hasItems(1)
        ->create([
            'company_id' => $company->id,
            'template_name' => Invoice::TEMPLATE_LORRY_RECEIPT,
            'unique_hash' => 'lorry-rupees-preview',
        ]);

    $addNumberField = function (string $label, int $value) use ($company, $invoice): void {
        $customField = CustomField::factory()->create([
            'company_id' => $company->id,
            'model_type' => 'Invoice',
            'name' => $label,
            'label' => $label,
            'slug' => clean_slug('Invoice', $label),
            'type' => 'Number',
        ]);

        $invoice->fields()->create([
            'company_id' => $company->id,
            'custom_field_id' => $customField->id,
            'type' => 'Number',
            'number_answer' => $value,
        ]);
    };

    $addNumberField('Lorry Hire', 2000);
    $addNumberField('Add Other Charges', 300);
    $addNumberField('Advance Paid Rs', 500);
    $addNumberField('Add Detention Rs.', 50);
    $addNumberField('Extra Hire Rs', 25);
    $addNumberField('Other Rs', 10);
    $addNumberField('Less Adv. at other branch', 100);
    $addNumberField('Less Deduction for Claims', 20);

    $response = get("/invoices/pdf/{$invoice->unique_hash}?preview=1&template_name=".Invoice::TEMPLATE_LORRY_RECEIPT);

    $response->assertOk();

    expect($response->getContent())
        ->toContain('style="left:440pt; top:437pt; width:70pt;">2000</div>')
        ->toContain('style="left:440pt; top:453pt; width:70pt;">300</div>')
        ->toContain('style="left:440pt; top:469pt; width:70pt;">2300</div>')
        ->toContain('style="left:440pt; top:485pt; width:70pt;">500</div>')
        ->toContain('style="left:440pt; top:501pt; width:70pt;">1800</div>')
        ->toContain('style="left:450pt; top:647pt; width:58pt;">85</div>')
        ->toContain('style="left:450pt; top:661pt; width:58pt;">1885</div>')
        ->toContain('style="left:450pt; top:675pt; width:58pt;">120</div>')
        ->toContain('style="left:450pt; top:690pt; width:58pt;">1765</div>');
});
