<?php

use App\Models\Invoice;
use App\Models\User;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Silber\Bouncer\BouncerFacade;

uses(\Tests\TestCase::class, RefreshDatabase::class);

it('checks if invoice number exists correctly', function () {
    $company = Company::factory()->create();
    $user = User::factory()->create();

    // Attach user to company
    $user->companies()->attach($company->id, ['role' => 'admin']);

    // Set Bouncer scope and allow the user to view invoices
    BouncerFacade::scope()->to($company->id);
    BouncerFacade::allow($user)->to('view-invoice', Invoice::class);

    // Authenticate user
    $this->actingAs($user);

    // Create an invoice
    Invoice::factory()->create([
        'company_id' => $company->id,
        'invoice_number' => 'INV-0001',
        'template_name' => 'lr_receipt',
    ]);

    // Send request checking existing invoice number
    $response = $this->withHeaders([
        'company' => $company->id,
    ])->getJson('/api/v1/invoices/check-number?invoice_number=INV-0001&template_name=lr_receipt');

    $response->assertStatus(200);
    $response->assertJson(['exists' => true]);

    // Send request checking unique invoice number
    $response = $this->withHeaders([
        'company' => $company->id,
    ])->getJson('/api/v1/invoices/check-number?invoice_number=INV-0002&template_name=lr_receipt');

    $response->assertStatus(200);
    $response->assertJson(['exists' => false]);
});
