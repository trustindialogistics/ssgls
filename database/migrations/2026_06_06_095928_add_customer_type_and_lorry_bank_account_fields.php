<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add type column to customers
        if (!Schema::hasColumn('customers', 'type')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->string('type')->default('CUSTOMER')->index();
            });
        }

        // 2. Add bank_account_no to lorry_party_profiles
        if (!Schema::hasColumn('lorry_party_profiles', 'bank_account_no')) {
            Schema::table('lorry_party_profiles', function (Blueprint $table) {
                $table->string('bank_account_no')->nullable();
            });
        }

        // 3. Add bank account columns to lorry_receipts
        Schema::table('lorry_receipts', function (Blueprint $table) {
            if (!Schema::hasColumn('lorry_receipts', 'owner_bank_account_no')) {
                $table->string('owner_bank_account_no')->nullable();
            }
            if (!Schema::hasColumn('lorry_receipts', 'driver_bank_account_no')) {
                $table->string('driver_bank_account_no')->nullable();
            }
            if (!Schema::hasColumn('lorry_receipts', 'broker_bank_account_no')) {
                $table->string('broker_bank_account_no')->nullable();
            }
        });

        // 4. Update Advice No custom field to Broker Pan No
        DB::table('custom_fields')
            ->where('model_type', 'Invoice')
            ->where('name', 'Advice No')
            ->update([
                'name' => 'Broker Pan No',
                'label' => 'Broker Pan No',
                'slug' => 'CUSTOM_Invoice_BROKER_PAN_NO'
            ]);

        // 5. Create associated Customer records for all existing profiles
        $profiles = DB::table('lorry_party_profiles')->get();
        foreach ($profiles as $profile) {
            $companyId = $profile->company_id;
            
            // Get default currency for company
            $currencyIdSetting = DB::table('company_settings')
                ->where('company_id', $companyId)
                ->where('key', 'currency_id')
                ->value('value');
            $currencyId = $currencyIdSetting ? (int) $currencyIdSetting : 1;

            if ($profile->customer_id) {
                // If it already has customer_id, update customer's type to match profile type
                DB::table('customers')
                    ->where('id', $profile->customer_id)
                    ->update([
                        'type' => $profile->type,
                        'phone' => $profile->phone
                    ]);
            } else {
                // Create a new Customer record
                $customerId = DB::table('customers')->insertGetId([
                    'company_id' => $companyId,
                    'name' => $profile->name ?: ($profile->code ?: 'Unnamed Profile'),
                    'phone' => $profile->phone,
                    'type' => $profile->type,
                    'currency_id' => $currencyId,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                // Update the profile's customer_id
                DB::table('lorry_party_profiles')
                    ->where('id', $profile->id)
                    ->update(['customer_id' => $customerId]);

                // Create billing address if address is set
                if (!empty($profile->address)) {
                    DB::table('addresses')->insert([
                        'company_id' => $companyId,
                        'customer_id' => $customerId,
                        'type' => 'BILLING',
                        'name' => $profile->name ?: 'Unnamed Profile',
                        'address_street_1' => $profile->address,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('customers', 'type')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn('type');
            });
        }

        if (Schema::hasColumn('lorry_party_profiles', 'bank_account_no')) {
            Schema::table('lorry_party_profiles', function (Blueprint $table) {
                $table->dropColumn('bank_account_no');
            });
        }

        Schema::table('lorry_receipts', function (Blueprint $table) {
            if (Schema::hasColumn('lorry_receipts', 'owner_bank_account_no')) {
                $table->dropColumn('owner_bank_account_no');
            }
            if (Schema::hasColumn('lorry_receipts', 'driver_bank_account_no')) {
                $table->dropColumn('driver_bank_account_no');
            }
            if (Schema::hasColumn('lorry_receipts', 'broker_bank_account_no')) {
                $table->dropColumn('broker_bank_account_no');
            }
        });

        DB::table('custom_fields')
            ->where('model_type', 'Invoice')
            ->where('name', 'Broker Pan No')
            ->update([
                'name' => 'Advice No',
                'label' => 'Advice No',
                'slug' => 'CUSTOM_Invoice_ADVICE_NO'
            ]);
    }
};
