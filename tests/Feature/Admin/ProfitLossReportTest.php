<?php

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\LorryReceipt;
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
    Sanctum::actingAs(
        $user,
        ['*']
    );
});

test('profit loss report income uses lr receipt profit loss', function () {
    $user = User::find(1);
    $company = $user->companies()->first();

    $lrReceipt = Invoice::factory()->create([
        'company_id' => $company->id,
        'template_name' => Invoice::TEMPLATE_LR_RECEIPT,
        'invoice_number' => 'LR-PROFIT-LOSS-1',
        'invoice_date' => '2030-02-15',
        'total' => 0,
        'due_amount' => 0,
    ]);

    LorryReceipt::create([
        'company_id' => $company->id,
        'received_no_bilties' => $lrReceipt->invoice_number,
        'advance_amount' => '5000',
    ]);

    Invoice::factory()->create([
        'company_id' => $company->id,
        'template_name' => Invoice::TEMPLATE_OFFICE_INVOICE,
        'reference_number' => $lrReceipt->invoice_number,
        'total' => 12500,
    ]);

    Expense::factory()->create([
        'company_id' => $company->id,
        'expense_date' => '2030-02-20',
        'amount' => 2500,
        'base_amount' => 2500,
    ]);

    get("/reports/profit-loss/{$company->unique_hash}?from_date=2030-02-01&to_date=2030-02-28&preview=1")
        ->assertOk()
        ->assertSee('-4,875.00')
        ->assertDontSee('-4,900.00');
});
