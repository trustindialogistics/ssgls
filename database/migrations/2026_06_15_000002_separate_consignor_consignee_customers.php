<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Models\Customer;

return new class extends Migration
{
    public function up(): void
    {
        // Find all LR Receipt invoices with customer_id
        $lrInvoices = DB::table('invoices')
            ->where('template_name', 'lr_receipt')
            ->whereNotNull('customer_id')
            ->get();

        $consignorField = DB::table('custom_fields')
            ->where('model_type', 'Invoice')
            ->where('name', 'Consignor')
            ->first();

        foreach ($lrInvoices as $invoice) {
            $oldCustomerId = $invoice->customer_id;

            // Get Consignor name from custom field
            $consignorValue = null;
            if ($consignorField) {
                $consignorValue = DB::table('custom_field_values')
                    ->where('custom_field_valuable_type', 'App\Models\Invoice')
                    ->where('custom_field_valuable_id', $invoice->id)
                    ->where('custom_field_id', $consignorField->id)
                    ->value('string_answer');
            }

            $consignorLines = $consignorValue ? explode("\n", $consignorValue) : [];
            $consignorName = trim($consignorLines[0] ?? '');

            if (empty($consignorName)) {
                continue;
            }

            // Find or create Consignor customer (type=CUSTOMER)
            $consignorCustomer = Customer::where('company_id', $invoice->company_id)
                ->where('type', 'CUSTOMER')
                ->where('name', $consignorName)
                ->first();

            if (!$consignorCustomer) {
                $consignorCustomer = Customer::create([
                    'company_id' => $invoice->company_id,
                    'name' => $consignorName,
                    'type' => 'CUSTOMER',
                    'currency_id' => $invoice->currency_id ?? 1,
                ]);

                // Create billing address if address block is present
                $actualAddressBlock = implode("\n", array_slice($consignorLines, 1));
                if (trim($actualAddressBlock) !== '') {
                    [$street1, $street2] = $this->splitAddress($actualAddressBlock);
                    $consignorCustomer->billingAddress()->create([
                        'company_id' => $invoice->company_id,
                        'name' => $consignorName,
                        'address_street_1' => $street1,
                        'address_street_2' => $street2,
                        'country_id' => 1,
                        'type' => 'billing',
                    ]);
                }
            }

            // Check if current customer is used elsewhere as a Consignor/CUSTOMER
            $usedElsewhere = DB::table('invoices')
                ->where('customer_id', $oldCustomerId)
                ->where('template_name', '<>', 'lr_receipt')
                ->exists()
                || DB::table('estimates')->where('customer_id', $oldCustomerId)->exists()
                || DB::table('recurring_invoices')->where('customer_id', $oldCustomerId)->exists()
                || DB::table('payments')->where('customer_id', $oldCustomerId)->exists()
                || DB::table('lorry_party_profiles')->where('customer_id', $oldCustomerId)->exists();

            if ($usedElsewhere) {
                // Find or create a CONSIGNEE copy
                $oldCustomer = DB::table('customers')->where('id', $oldCustomerId)->first();
                if ($oldCustomer) {
                    $consigneeCustomer = Customer::where('company_id', $invoice->company_id)
                        ->where('type', 'CONSIGNEE')
                        ->where('name', $oldCustomer->name)
                        ->first();

                    if (!$consigneeCustomer) {
                        $consigneeCustomer = Customer::create([
                            'company_id' => $oldCustomer->company_id,
                            'name' => $oldCustomer->name,
                            'email' => $oldCustomer->email ? $oldCustomer->email . '-consignee' : null,
                            'phone' => $oldCustomer->phone,
                            'type' => 'CONSIGNEE',
                            'currency_id' => $oldCustomer->currency_id,
                            'tax_id' => $oldCustomer->tax_id,
                        ]);

                        // Copy billing & shipping addresses
                        $addresses = DB::table('addresses')->where('customer_id', $oldCustomerId)->get();
                        foreach ($addresses as $address) {
                            $addrData = (array) $address;
                            unset($addrData['id']);
                            $addrData['customer_id'] = $consigneeCustomer->id;
                            DB::table('addresses')->insert($addrData);
                        }
                    }
                    $consigneeCustomerId = $consigneeCustomer->id;
                } else {
                    $consigneeCustomerId = null;
                }
            } else {
                // Change type to CONSIGNEE directly
                DB::table('customers')->where('id', $oldCustomerId)->update(['type' => 'CONSIGNEE']);
                $consigneeCustomerId = $oldCustomerId;
            }

            // Update LR Receipt invoice with correct Customer mappings
            if ($consigneeCustomerId) {
                DB::table('invoices')->where('id', $invoice->id)->update([
                    'consignee_customer_id' => $consigneeCustomerId,
                    'customer_id' => $consignorCustomer->id,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Reverting this migration is not recommended as it duplicates customer mappings.
    }

    private function splitAddress(string $addressBlock, int $maxLineLength = 45): array
    {
        $addressBlock = trim($addressBlock);
        if ($addressBlock === '') {
            return ['', ''];
        }

        $lines = preg_split('/\r\n|\r|\n/', $addressBlock);
        $wrappedLines = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            if (mb_strlen($line) > $maxLineLength) {
                $wrapped = wordwrap($line, $maxLineLength, "\n", false);
                $parts = explode("\n", $wrapped);
                foreach ($parts as $part) {
                    $wrappedLines[] = trim($part);
                }
            } else {
                $wrappedLines[] = $line;
            }
        }

        $street1 = isset($wrappedLines[0]) ? $wrappedLines[0] : '';
        $street2 = implode("\n", array_slice($wrappedLines, 1));

        return [$street1, $street2];
    }
};
