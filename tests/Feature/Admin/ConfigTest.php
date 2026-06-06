<?php

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;

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

test('get all languages', function () {
    $key = 'languages';

    getJson('api/v1/config?key='.$key)
        ->assertOk();
});

test('get all fiscal years', function () {
    $key = 'fiscal_years';

    getJson('api/v1/config?key='.$key)
        ->assertOk();
});

test('get all convert estimate options', function () {
    $key = 'convert_estimate_options';

    getJson('api/v1/config?key='.$key)
        ->assertOk();
});

test('get all retrospective edits', function () {
    $key = 'retrospective_edits';

    getJson('api/v1/config?key='.$key)
        ->assertOk();
});

test('get all currency converter servers', function () {
    $key = 'currency_converter_servers';

    getJson('api/v1/config?key='.$key)
        ->assertOk();
});

test('get all exchange rate drivers', function () {
    $key = 'exchange_rate_drivers';

    getJson('api/v1/config?key='.$key)
        ->assertOk();
});

test('get all custom field models', function () {
    $key = 'custom_field_models';

    getJson('api/v1/config?key='.$key)
        ->assertOk();
});

test('sqlite connection enables concurrent access settings', function () {
    expect(config('database.connections.sqlite.busy_timeout'))->toBe(5000)
        ->and(config('database.connections.sqlite.journal_mode'))->toBe('wal')
        ->and(config('database.connections.sqlite.synchronous'))->toBe('NORMAL');
});

test('transport menu entries use invoice authorization model', function () {
    $transportEntries = collect(config('invoiceshelf.main_menu'))
        ->whereIn('name', [
            'Owner List',
            'Driver List',
            'Broker List',
            'LR Receipts',
            'Lorry Receipts',
        ]);

    expect($transportEntries)->toHaveCount(5);

    $transportEntries->each(function (array $entry) {
        expect($entry['model'])->toBe(Invoice::class);
    });
});
