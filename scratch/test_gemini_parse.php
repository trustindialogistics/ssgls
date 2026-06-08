<?php
// Read Gemini key from .env
$env = file_get_contents('D:/ssgls/.env');
preg_match('/GEMINI_API_KEY=["\']?([^"\']+)["\']?/', $env, $matches);
$apiKey = $matches[1] ?? null;

if (!$apiKey) {
    echo "No API key found in .env\n";
    exit;
}

// Locate a media image file to test with
$brainDir = 'C:/Users/prach/.gemini/antigravity/brain/c784a7d2-4eac-4f9f-82e7-b3f9ed7ac3b5/';
$imageFile = $brainDir . 'media__1780854009046.jpg';

if (!file_exists($imageFile)) {
    // Try png
    $imageFile = $brainDir . 'media__1780854033229.png';
}

if (!file_exists($imageFile)) {
    echo "No test image file found!\n";
    exit;
}

echo "Testing with image: $imageFile\n";
$mimeType = str_ends_with($imageFile, '.jpg') ? 'image/jpeg' : 'image/png';
$base64Data = base64_encode(file_get_contents($imageFile));

$schema = [
    'type' => 'OBJECT',
    'properties' => [
        'consignor_name' => ['type' => 'STRING', 'description' => 'Name of the consignor (sender) company or person'],
        'consignor_gstin' => ['type' => 'STRING', 'description' => 'GSTIN of the consignor, e.g. 24ABCDE1234F1Z5'],
        'consignor_phone' => ['type' => 'STRING', 'description' => 'Phone number of the consignor'],
        'consignor_address' => ['type' => 'STRING', 'description' => 'Full address of the consignor'],
        'consignee_name' => ['type' => 'STRING', 'description' => 'Name of the consignee (receiver) company or person'],
        'consignee_gstin' => ['type' => 'STRING', 'description' => 'GSTIN of the consignee, e.g. 24ABCDE1234F1Z5'],
        'consignee_phone' => ['type' => 'STRING', 'description' => 'Phone number of the consignee'],
        'consignee_address' => ['type' => 'STRING', 'description' => 'Full address of the consignee'],
        'from' => ['type' => 'STRING', 'description' => 'Origin/dispatch city or place'],
        'to' => ['type' => 'STRING', 'description' => 'Destination city or place'],
    ],
    'required' => [],
];

$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $apiKey;

$payload = [
    'contents' => [
        [
            'parts' => [
                [
                    'text' => 'You are an expert document parsing assistant. Extract all data from this invoice or E-way bill to populate a Lorry Receipt (LR) record. Return the values in the exact JSON format specified by the response schema. If a value is not found, leave it as null or empty. Do not invent any data.',
                ],
                [
                    'inlineData' => [
                        'mimeType' => $mimeType,
                        'data' => $base64Data,
                    ],
                ],
            ],
        ],
    ],
    'generationConfig' => [
        'responseMimeType' => 'application/json',
        'responseSchema' => $schema,
    ],
];

// Make request using curl if run locally on host
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
$result = json_decode($response, true);
$text = $result['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
echo "Gemini Raw Output:\n" . json_encode(json_decode($text, true), JSON_PRETTY_PRINT) . "\n";
