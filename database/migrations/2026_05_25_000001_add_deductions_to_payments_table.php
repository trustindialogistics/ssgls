<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedBigInteger('tds_amount')->default(0)->after('amount');
            $table->unsignedBigInteger('deduction_amount')->default(0)->after('tds_amount');
            $table->string('invoice_paid_status')->nullable()->after('deduction_amount');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'tds_amount',
                'deduction_amount',
                'invoice_paid_status',
            ]);
        });
    }
};
