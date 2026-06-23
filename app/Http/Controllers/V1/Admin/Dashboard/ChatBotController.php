<?php

namespace App\Http\Controllers\V1\Admin\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatBotController extends Controller
{
    protected $geminiApiKey;
    protected $geminiModel = 'gemini-2.5-flash';
    
    protected $forbiddenKeywords = [
        'DELETE', 'UPDATE', 'INSERT', 'REPLACE',
        'DROP', 'TRUNCATE', 'ALTER', 'CREATE',
        'EXEC', 'EXECUTE', 'CALL', 'GRANT', 'REVOKE',
        '--', ';', '/*', '*/', 'xp_', 'sp_'
    ];

    public function __construct()
    {
        $this->geminiApiKey = config('services.gemini.key');
    }

    public function __invoke(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $userMessage = $request->input('message');

        if (!$this->geminiApiKey) {
            return response()->json([
                'answer' => 'ChatBOT is not configured. Please add GEMINI_API_KEY to your .env file.',
                'sql' => null,
                'data' => null,
            ]);
        }

        $intent = $this->classifyIntent($userMessage);

        if ($intent === 'high_demand') {
            return response()->json([
                'answer' => "The ChatBOT is currently experiencing high demand or rate limits. Please try again in a few moments.",
                'sql' => null,
                'data' => null,
            ]);
        }

        if ($intent === 'generic') {
            return $this->askGeneric($userMessage);
        }

        // Generate SQL with retry loop for self-correction
        $maxRetries = 2;
        $retryCount = 0;
        $lastError = null;

        while ($retryCount <= $maxRetries) {
            $sql = $this->generateSQL($userMessage, $lastError);

            if (!$this->isQuerySafe($sql)) {
                Log::warning("Blocked unsafe SQL query from chatbot: $sql");
                return response()->json([
                    'answer' => "I'm sorry, I cannot execute that query for security reasons.",
                    'sql' => null,
                    'data' => null,
                ]);
            }

            try {
                $results = $this->executeQuery($sql);
                return $this->formatAnswer($userMessage, $sql, $results);
            } catch (\Exception $e) {
                $lastError = $e->getMessage();

                if ($lastError === 'GEMINI_HIGH_DEMAND') {
                    return response()->json([
                        'answer' => "The ChatBOT is currently experiencing high demand or rate limits. Please try again in a few moments.",
                        'sql' => null,
                        'data' => null,
                    ]);
                }

                Log::warning("ChatBOT SQL Error (attempt " . ($retryCount + 1) . "): " . $lastError);
                Log::warning("SQL Query: $sql");
                
                $retryCount++;
                
                // If we've exhausted retries, return error to user
                if ($retryCount > $maxRetries) {
                    Log::error("ChatBOT exhausted retries. Final error: " . $lastError);
                    return response()->json([
                        'answer' => "I'm having trouble fetching that data. Please try rephrasing your question.",
                        'sql' => $sql,
                        'data' => null,
                        'error' => $lastError,
                    ]);
                }
                // Otherwise, loop continues and Gemini will try to fix the SQL
            }
        }
    }

    private function classifyIntent(string $question): string
    {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/'.$this->geminiModel.':generateContent?key='.$this->geminiApiKey;

        $response = Http::post($url, [
            'contents' => [[
                'parts' => [[
                    'text' => "Classify as 'data' or 'generic':
- 'data': Questions about invoices, customers, payments, expenses, business data
- 'generic': Greetings, general questions, non-business questions

Question: '$question'

Respond with ONLY one word: 'data' or 'generic'"
                ]]
            ]]
        ]);

        if ($response->failed()) {
            Log::error("Gemini API Error in classifyIntent: " . $response->body());
            if ($response->status() === 503 || $response->status() === 429) {
                return 'high_demand';
            }
        }

        $result = $response->json();
        $intent = $result['candidates'][0]['content']['parts'][0]['text'] ?? 'generic';
        return trim(strtolower($intent));
    }

    private function askGeneric(string $question): \Illuminate\Http\JsonResponse
    {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/'.$this->geminiModel.':generateContent?key='.$this->geminiApiKey;

        $response = Http::post($url, [
            'contents' => [[
                'parts' => [[
                    'text' => "You are a helpful business assistant for HisabKitabb Services, a transportation and logistics company.

User: $question

Assistant:"
                ]]
            ]]
        ]);

        if ($response->failed()) {
            Log::error("Gemini API Error in askGeneric: " . $response->body());
            if ($response->status() === 503 || $response->status() === 429) {
                return response()->json([
                    'answer' => "The ChatBOT is currently experiencing high demand or rate limits. Please try again in a few moments.",
                    'sql' => null,
                    'data' => null,
                ]);
            }
        }

        $result = $response->json();
        $answer = $result['candidates'][0]['content']['parts'][0]['text'] ?? "I'm sorry, I couldn't process that.";

        return response()->json([
            'answer' => $answer,
            'sql' => null,
            'data' => null,
        ]);
    }

    private function generateSQL(string $question, ?string $lastError = null): string
    {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/'.$this->geminiModel.':generateContent?key='.$this->geminiApiKey;

        $schema = $this->getSchemaInfo();

        $errorContext = $lastError 
            ? "\n\nPREVIOUS ATTEMPT FAILED WITH ERROR: $lastError\nPlease fix this error and generate a correct SQL query."
            : '';

        $response = Http::post($url, [
            'contents' => [[
                'parts' => [[
                    'text' => "You are a SQL generator for HisabKitabb Services.

DATABASE SCHEMA:
$schema

CRITICAL RULES:
1. ONLY generate SELECT queries - NEVER DELETE, UPDATE, INSERT, DROP, TRUNCATE
2. Use proper JOIN syntax
3. Always qualify column names with table names
4. Use LIMIT 100 to prevent too many results
5. Return ONLY the SQL query, no explanations

User Question: '$question'$errorContext

Generate the SQL query:"
                ]]
            ]]
        ]);

        if ($response->failed()) {
            Log::error("Gemini API Error in generateSQL: " . $response->body());
            if ($response->status() === 503 || $response->status() === 429) {
                throw new \Exception("GEMINI_HIGH_DEMAND");
            }
        }

        $result = $response->json();
        $sql = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';

        preg_match('/```sql\s*(.*?)\s*```/s', $sql, $matches);
        $sql = $matches[1] ?? $sql;
        $sql = trim($sql, "` \n\r\t");
        $sql = rtrim($sql, ';');
        
        if (!str_contains(strtoupper($sql), 'LIMIT')) {
            $sql = $sql . ' LIMIT 100';
        }

        return $sql;
    }

    private function isQuerySafe(string $sql): bool
    {
        $sql = strtoupper(trim($sql));
        
        if (!str_starts_with($sql, 'SELECT')) {
            return false;
        }

        // Check for forbidden command keywords as whole words
        // This prevents columns like created_at or updated_at from being blocked
        $commandKeywords = [
            'DELETE', 'UPDATE', 'INSERT', 'REPLACE',
            'DROP', 'TRUNCATE', 'ALTER', 'CREATE',
            'EXEC', 'EXECUTE', 'CALL', 'GRANT', 'REVOKE'
        ];
        
        $pattern = '/\b(' . implode('|', $commandKeywords) . ')\b/i';
        if (preg_match($pattern, $sql)) {
            return false;
        }

        // Check for comments and special patterns
        $specialKeywords = ['--', '/*', '*/', 'xp_', 'sp_'];
        foreach ($specialKeywords as $keyword) {
            if (strpos($sql, $keyword) !== false) {
                return false;
            }
        }
        
        // Prevent query chaining by ensuring no semicolons exist except at the end
        $cleanSql = rtrim($sql, ';');
        if (strpos($cleanSql, ';') !== false) {
            return false;
        }
        
        return true;
    }

    private function executeQuery(string $sql): array
    {
        $results = DB::select($sql);
        return array_map(function($item) {
            return (array) $item;
        }, $results);
    }

    private function formatAnswer(string $question, string $sql, array $results): \Illuminate\Http\JsonResponse
    {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/'.$this->geminiModel.':generateContent?key='.$this->geminiApiKey;

        $resultsJson = json_encode($results, JSON_PRETTY_PRINT);

        $response = Http::post($url, [
            'contents' => [[
                'parts' => [[
                    'text' => "User asked: '$question'

SQL Results:
$resultsJson

Provide a helpful, natural language answer. Include key insights and totals.
Format numbers with ₹ symbol for currency. Keep it concise."
                ]]
            ]]
        ]);

        if ($response->failed()) {
            Log::error("Gemini API Error in formatAnswer: " . $response->body());
        }

        $result = $response->json();
        $answer = $result['candidates'][0]['content']['parts'][0]['text'] ?? "I found the data but couldn't format a response.";

        return response()->json([
            'answer' => $answer,
            'sql' => $sql,
            'data' => $results,
            'count' => count($results),
        ]);
    }

    private function getSchemaInfo(): string
    {
        return "
DATABASE: HisabKitabb Services - Transportation Management System

=== INVOICES TABLE (Main table for LR Receipts) ===
Table: invoices
Columns:
- id (integer, primary key)
- invoice_number (string) - Docket number
- customer_id (integer, foreign key to customers.id) - Consignor
- consignee_customer_id (integer, foreign key to customers.id) - Consignee
- total (decimal)
- amount_debit (decimal) - Receivable amount
- amount_credit (decimal) - Amount credited
- status (string) - DRAFT, SENT, DUE, COMPLETED
- invoice_date (date)
- created_at (datetime)
- updated_at (datetime)
- template_name (string) - 'lr_receipt' for LR Receipts
- creator_id (integer) - User who created
- updated_by (integer) - User who last updated

=== LORRY RECEIPTS TABLE (Separate table for Lorry Receipts) ===
Table: lorry_receipts
Columns:
- id (integer, primary key)
- company_id (integer)
- owner_customer_id (integer, FK to customers.id) - Owner
- driver_customer_id (integer, FK to customers.id) - Driver
- broker_customer_id (integer, FK to customers.id) - Broker
- unique_hash (string)
- contract_no (string)
- from_code (string) - Origin code
- from_name (string) - Origin name
- to_code (string) - Destination code
- to_name (string) - Destination name
- challan_no (string) - Challan number
- no_of_pages (string)
- no_of_pkgs (string) - Number of packages
- actual_weight (string)
- charge_weight (string)
- lorry_no (string) - Lorry/Vehicle number
- rate (string)
- distance_kms (string)
- owner_code (string)
- owner_name (string)
- owner_address (text)
- owner_phone (string)
- driver_name (string)
- driver_address (text)
- driver_place (string)
- driver_licence_no (string)
- driver_licence_date (string)
- broker_name (string)
- broker_address (text)
- broker_phone (string)
- paid_to (string)
- lorry_hire_amount (string)
- advance_amount (string)
- advance_on (string)
- balance_amount (string)
- net_amount_payable (string)
- grand_total_amount (string)
- final_balance_on (string)
- created_at (datetime)
- updated_at (datetime)
- creator_id (integer)
- updated_by (integer)

=== CUSTOMERS TABLE (Stores Consignor, Consignee, Owners, Drivers, Brokers) ===
Table: customers
Columns:
- id (integer, primary key)
- name (string) - Customer name
- display_name (string)
- phone (string)
- email (string)
- tax_id (string) - GSTIN
- type (string) - CUSTOMER, CONSIGNEE, OWNER, DRIVER, BROKER
- created_at (datetime)
- updated_at (datetime)
- company_id (integer)
- creator_id (integer)
- updated_by (integer)

=== PAYMENTS TABLE ===
Table: payments
Columns:
- id (integer, primary key)
- invoice_id (integer, FK to invoices.id)
- amount (decimal)
- payment_date (date)
- payment_number (string)
- payment_method (string)
- created_at (datetime)
- updated_at (datetime)
- creator_id (integer)
- updated_by (integer)

=== EXPENSES TABLE ===
Table: expenses
Columns:
- id (integer, primary key)
- expense_number (string)
- amount (decimal)
- expense_date (date)
- notes (text)
- category_id (integer) - FK to expense_categories
- created_at (datetime)
- updated_at (datetime)
- creator_id (integer)
- updated_by (integer)

=== EXPENSE CATEGORIES TABLE ===
Table: expense_categories
Columns:
- id (integer, primary key)
- name (string)
- created_at (datetime)
- updated_at (datetime)

=== LORRY PARTY PROFILES TABLE ===
Table: lorry_party_profiles
Columns:
- id (integer, primary key)
- name (string)
- type (string) - OWNER, DRIVER, BROKER
- phone (string)
- address (text)
- rc_front_path (string) - For OWNER
- rc_back_path (string) - For OWNER
- pan_front_path (string) - For OWNER
- license_front_path (string) - For DRIVER
- license_back_path (string) - For DRIVER
- aadhar_front_path (string) - For BROKER
- aadhar_back_path (string) - For BROKER
- created_at (datetime)
- updated_at (datetime)
- creator_id (integer)
- updated_by (integer)

=== ITEMS TABLE ===
Table: items
Columns:
- id (integer, primary key)
- name (string)
- price (decimal)
- unit_name (string)
- created_at (datetime)
- updated_at (datetime)
- creator_id (integer)
- updated_by (integer)

=== IMPORTANT NOTES FOR SQL QUERIES ===

1. To get LR Receipts: SELECT * FROM invoices WHERE template_name = 'lr_receipt'
2. To get Lorry Receipts: SELECT * FROM lorry_receipts
3. To get Consignor: SELECT * FROM customers WHERE type = 'CUSTOMER'
4. To get Consignee: SELECT * FROM customers WHERE type = 'CONSIGNEE'
5. To get Owner: SELECT * FROM customers WHERE type = 'OWNER' OR FROM lorry_party_profiles WHERE type = 'OWNER'
6. To get Driver: SELECT * FROM customers WHERE type = 'DRIVER' OR FROM lorry_party_profiles WHERE type = 'DRIVER'
7. To get Broker: SELECT * FROM customers WHERE type = 'BROKER' OR FROM lorry_party_profiles WHERE type = 'BROKER'

=== EXAMPLE QUERIES ===

-- Total LR Receipts this month:
SELECT COUNT(*) as count, SUM(amount_debit) as total FROM invoices 
WHERE template_name = 'lr_receipt' AND MONTH(invoice_date) = MONTH(CURRENT_DATE())

-- Total Lorry Receipts this month:
SELECT COUNT(*) as count, SUM(lorry_hire_amount) as total FROM lorry_receipts
WHERE MONTH(created_at) = MONTH(CURRENT_DATE())

-- Unpaid invoices:
SELECT * FROM invoices WHERE status = 'DUE'

-- Top customers by invoice amount:
SELECT c.name, SUM(i.total) as total_amount 
FROM invoices i 
JOIN customers c ON i.customer_id = c.id 
GROUP BY c.name 
ORDER BY total_amount DESC 
LIMIT 5

-- Get Owner details from Lorry Receipt:
SELECT lr.owner_name, lr.owner_phone, lr.owner_address
FROM lorry_receipts lr
WHERE lr.owner_customer_id = 1

-- Get all party profiles:
SELECT name, type, phone FROM lorry_party_profiles
        ";
    }
}
