<?php

namespace App\Http\Controllers\V1\Admin\LorryPartyProfile;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LorryPartyAutoFillController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $this->authorize('create', Invoice::class);

        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
        ]);

        $apiKey = config('services.gemini.key');
        if (! $apiKey) {
            return response()->json([
                'error' => 'Gemini API Key is not configured. Please add GEMINI_API_KEY to your .env file.',
            ], 422);
        }

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());
        if (empty($extension)) {
            $extension = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
        }
        $extension = strtolower($extension);

        $mimeMap = [
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
        ];

        if (! isset($mimeMap[$extension])) {
            return response()->json([
                'error' => 'Unsupported file type. Please upload a PDF or image (JPEG/PNG).',
            ], 422);
        }

        $mimeType = $mimeMap[$extension];
        $base64Data = base64_encode(file_get_contents($file->getRealPath()));

        $schema = [
            'type' => 'OBJECT',
            'properties' => [
                'name' => ['type' => 'STRING', 'description' => 'Full Name of the card holder'],
                'phone' => ['type' => 'STRING', 'description' => 'Mobile/Phone number if printed on the card'],
                'address' => ['type' => 'STRING', 'description' => 'Full residential or billing address printed on the card (including street, state, city, pincode)'],
                'id_type' => ['type' => 'STRING', 'enum' => ['AADHAAR', 'PAN', 'DRIVING_LICENSE', 'OTHER'], 'description' => 'Type of document uploaded'],
                'id_number' => ['type' => 'STRING', 'description' => 'Document ID number (e.g. Aadhaar number, PAN number, or Driving License number)'],
                'licence_date' => ['type' => 'STRING', 'description' => 'Licence issued date in YYYY-MM-DD format (only for Driving License)'],
                'valid_up_to' => ['type' => 'STRING', 'description' => 'Licence validity expiration date in YYYY-MM-DD format (only for Driving License)'],
                'rto_address' => ['type' => 'STRING', 'description' => 'Licence RTO / issuing authority location or address (only for Driving License)'],
                'place' => ['type' => 'STRING', 'description' => 'Place or city name if mentioned'],
            ],
            'required' => [],
        ];

        $models = [
            'gemini-2.5-flash',
            'gemini-1.5-flash',
            'gemini-1.5-pro',
        ];

        $response = null;

        try {
            foreach ($models as $model) {
                $url = 'https://generativelanguage.googleapis.com/v1beta/models/'.$model.':generateContent?key='.$apiKey;

                $response = Http::retry(2, 1000, function ($response) {
                    return $response->status() === 429 || $response->status() >= 500;
                }, false)->post($url, [
                    'contents' => [
                        [
                            'parts' => [
                                [
                                    'text' => 'You are an expert document parsing assistant. Extract all data from this Indian identification document (Aadhaar Card, Driving License, PAN Card, etc.) to populate a Lorry Receipt Party Profile. Return the values in the exact JSON format specified by the response schema. If a value is not found or is on the other side of the card, leave it as null or empty. Do not invent any data.',
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
                ]);

                if ($response->successful()) {
                    break;
                }

                Log::warning("Gemini model {$model} failed with status {$response->status()} during party auto-fill. Trying next model...");
            }

            if ($response === null || $response->failed()) {
                $body = $response ? $response->body() : 'No response';
                Log::error('Gemini API party auto-fill failed after trying all models: '.$body);

                $status = $response ? $response->status() : 500;
                $errorMessage = 'Failed to parse document with Gemini API.';

                if ($status === 503) {
                    $errorMessage = 'The AI service is currently experiencing high demand. Please try again in a few seconds.';
                } elseif ($status === 429) {
                    $errorMessage = 'Rate limit exceeded. Please try again in a moment.';
                } elseif ($response) {
                    $errorMessage .= ' '.($response->json('error.message') ?? $response->body());
                }

                return response()->json([
                    'error' => $errorMessage,
                ], 500);
            }

            $result = $response->json();
            $text = $result['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
            $data = json_decode($text, true) ?? [];
            Log::info('Gemini parsed party text: ' . $text);

            return response()->json([
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            Log::error('LorryPartyAutoFillController error: '.$e->getMessage());
            return response()->json([
                'error' => 'An error occurred while parsing the document: '.$e->getMessage(),
            ], 500);
        }
    }
}
