<?php

use Illuminate\Support\Facades\Http;

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$key = config('services.gemini.key');
echo "API Key: " . substr($key, 0, 8) . "...\n\n";

$models = ['gemini-1.5-flash', 'gemini-1.5-pro', 'gemini-2.5-flash'];

foreach ($models as $model) {
    echo "Testing model: $model\n";
    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$key}";
    
    try {
        $response = Http::post($url, [
            'contents' => [[
                'parts' => [[
                    'text' => "Hello, reply with one word: 'Success'"
                ]]
            ]]
        ]);
        
        echo "Status: " . $response->status() . "\n";
        echo "Response: " . substr($response->body(), 0, 300) . "\n";
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
    echo "----------------------------------------\n\n";
}
