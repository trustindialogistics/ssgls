<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$lrReceipts = \App\Models\Invoice::where('template_name', \App\Models\Invoice::TEMPLATE_LR_RECEIPT)->get();
echo "=== LR RECEIPTS (DOCKETS) ===\n";
foreach ($lrReceipts as $lr) {
    echo "ID: " . $lr->id . " | Docket No: " . $lr->invoice_number . " | Amount Credit: " . $lr->amount_credit . " | Amount Debit: " . $lr->amount_debit . "\n";
}
