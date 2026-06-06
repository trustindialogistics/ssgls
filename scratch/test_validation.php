<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Http\Requests\InvoicesRequest;
use Illuminate\Support\Facades\Validator;

$request = new InvoicesRequest();
$request->initialize(
    [], // query
    ['template_name' => 'lorry_receipt', 'invoice_number' => '1234'], // request
    [], // attributes
    [], // cookies
    [], // files
    ['HTTP_COMPANY' => '1'] // server
);
$request->headers->set('company', '1');

$validator = Validator::make($request->all(), $request->rules(), $request->messages());
echo json_encode($validator->errors()->toArray(), JSON_PRETTY_PRINT) . PHP_EOL;
