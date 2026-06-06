<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $companyAddressFormat = '<h3><strong>{COMPANY_NAME}</strong></h3><p>{COMPANY_ADDRESS_STREET_1}</p><p>{COMPANY_ADDRESS_STREET_2}</p><p>{COMPANY_CITY} {COMPANY_STATE}</p><p>{COMPANY_COUNTRY}  {COMPANY_ZIP_CODE}</p><p>{COMPANY_PHONE}</p>';

        \App\Models\CompanySetting::where('key', 'estimate_company_address_format')
            ->update(['value' => $companyAddressFormat]);

        \App\Models\CompanySetting::where('key', 'invoice_company_address_format')
            ->update(['value' => $companyAddressFormat]);

        \App\Models\CompanySetting::where('key', 'payment_company_address_format')
            ->update(['value' => $companyAddressFormat]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
