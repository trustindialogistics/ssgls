<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'consignee_customer_id')) {
                $table->unsignedBigInteger('consignee_customer_id')->nullable();
                $table->foreign('consignee_customer_id')
                    ->references('id')
                    ->on('customers')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'consignee_customer_id')) {
                $table->dropForeign(['consignee_customer_id']);
                $table->dropColumn('consignee_customer_id');
            }
        });
    }
};
