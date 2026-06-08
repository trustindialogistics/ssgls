<?php
// Load env or config
$apiKey = getenv('GEMINI_API_KEY');
if (!$apiKey) {
    // try reading .env file manually
    $env = file_get_contents(__DIR__ . '/../.env');
    preg_match('/GEMINI_API_KEY=["\']?([^"\']+)["\']?/', $env, $matches);
    $apiKey = $matches[1] ?? null;
}

if (!$apiKey) {
    echo "No API key found!\n";
    exit;
}

$url = 'https://generativelanguage.googleapis.com/v1beta/models?key=' . $apiKey;
$response = file_get_contents($url);
$data = json_decode($response, true);
if (isset($data['models'])) {
    foreach ($data['models'] as $m) {
        if (str_contains($m['name'], 'gemini')) {
            echo $m['name'] . "\n";
        }
    }
} else {
    echo "Error or no models: " . $response . "\n";
}
