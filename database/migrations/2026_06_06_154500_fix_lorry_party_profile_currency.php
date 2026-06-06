<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Customer;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Customer::query()
            ->where('currency_id', 1)
            ->update(['currency_id' => 17]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Data correction, no reverse needed.
    }
};
