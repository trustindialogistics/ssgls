<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get all customers with their billing addresses
        $customers = DB::table('customers')
            ->join('addresses', 'customers.id', '=', 'addresses.customer_id')
            ->where('addresses.type', 'billing')
            ->where(function ($query) {
                $query->whereNull('customers.prefix')
                      ->orWhere(function ($q) {
                          $q->where('customers.prefix', 'not like', 'CNE%')
                            ->where('customers.prefix', 'not like', 'CNR%');
                      });
            })
            ->select('customers.id', 'customers.type', 'customers.prefix', 'addresses.city')
            ->get();

        // Track sequence numbers per type+city combination
        $sequences = [];

        foreach ($customers as $customer) {
            $cityName = trim(strtoupper($customer->city ?? ''));
            
            if (empty($cityName)) {
                continue;
            }

            // Get city code - first 3 letters (simplified, no dictionary)
            $cityCode = strtoupper(substr($cityName, 0, 3));

            // Determine type prefix
            $typePrefix = $customer->type === 'CONSIGNEE' ? 'CNE' : 'CNR';

            // Create new code
            $key = $typePrefix . '_' . $cityCode;
            
            if (!isset($sequences[$key])) {
                $sequences[$key] = 1;
            }

            $newCode = $typePrefix . $cityCode . str_pad($sequences[$key], 3, '0', STR_PAD_LEFT);
            $sequences[$key]++;

            // Update customer code
            DB::table('customers')
                ->where('id', $customer->id)
                ->update(['prefix' => $newCode]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot reliably revert - old format was sequential without type/city grouping
        // This migration is one-way only
    }
};
