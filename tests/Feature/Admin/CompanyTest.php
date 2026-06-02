<?php

use App\Http\Controllers\V1\Admin\Company\CompaniesController;
use App\Http\Requests\CompaniesRequest;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

beforeEach(function () {
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);
    Artisan::call('db:seed', ['--class' => 'DemoSeeder', '--force' => true]);

    $user = User::find(1);
    $this->withHeaders([
        'company' => $user->companies()->first()->id,
    ]);
    Sanctum::actingAs(
        $user,
        ['*']
    );
});

test('store user using a form request', function () {
    $this->assertActionUsesFormRequest(
        CompaniesController::class,
        'store',
        CompaniesRequest::class
    );
});

test('store company', function () {
    $company = Company::factory()->raw([
        'currency' => 12,
        'address' => [
            'country_id' => 12,
        ],
    ]);

    postJson('/api/v1/companies', $company)
        ->assertStatus(201);

    $company = collect($company)
        ->only([
            'name',
        ])
        ->toArray();

    $this->assertDatabaseHas('companies', $company);
});

test('delete company', function () {
    postJson('/api/v1/companies/delete', ['xyz'])
        ->assertStatus(422);
});

test('transfer ownership', function () {
    $company = Company::factory()->create();

    $user = User::factory()->create();

    postJson('/api/v1/transfer/ownership/'.$user->id)
        ->assertOk();
});

test('get companies', function () {
    getJson('/api/v1/companies')
        ->assertOk();
});

test('update company saves document identity and billing branch fields', function () {
    $company = User::find(1)->companies()->first();

    putJson('/api/v1/company', [
        'name' => $company->name,
        'tax_id' => $company->tax_id,
        'vat_id' => $company->vat_id,
        'gstin' => '24ABCDE1234F1Z5',
        'enrollment_no' => 'ENR-2026-001',
        'pan_no' => 'ABCDE1234F',
        'tagline' => 'A Cost Effective Distribution',
        'billing_branch_name_address' => "Vapi Billing Branch\nIndustrial Area",
        'notification_email' => 'accounts@example.com',
        'address' => [
            'country_id' => 1,
            'address_street_1' => 'Industrial Area',
            'address_street_2' => 'Vapi',
            'city' => 'Vapi',
            'state' => 'Gujarat',
            'zip' => '396195',
            'phone' => '9876543210',
        ],
    ])->assertOk();

    $this->assertDatabaseHas('companies', [
        'id' => $company->id,
        'gstin' => '24ABCDE1234F1Z5',
        'enrollment_no' => 'ENR-2026-001',
        'pan_no' => 'ABCDE1234F',
        'billing_branch_name_address' => "Vapi Billing Branch\nIndustrial Area",
    ]);

    expect(CompanySetting::getSetting('notification_email', $company->id))
        ->toBe('accounts@example.com');
});
