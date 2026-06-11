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
        Schema::table('invoices', function (Blueprint $table) {
            if (!Schema::hasColumn('invoices', 'amount_debit')) {
                $table->decimal('amount_debit', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('invoices', 'amount_credit')) {
                $table->decimal('amount_credit', 15, 2)->default(0);
            }
            if (!Schema::hasColumn('invoices', 'amount_debit_date')) {
                $table->string('amount_debit_date', 255)->nullable();
            }
            if (!Schema::hasColumn('invoices', 'amount_credit_date')) {
                $table->string('amount_credit_date', 255)->nullable();
            }
            if (!Schema::hasColumn('invoices', 'lorry_receipt_id')) {
                $table->unsignedBigInteger('lorry_receipt_id')->nullable();
                $table->foreign('lorry_receipt_id')->references('id')->on('lorry_receipts')->nullOnDelete();
            }
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            if (!Schema::hasColumn('invoice_items', 'consignment_number')) {
                $table->string('consignment_number', 50)->nullable();
                $table->index('consignment_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            if (Schema::hasColumn('invoice_items', 'consignment_number')) {
                $table->dropIndex(['consignment_number']);
                $table->dropColumn('consignment_number');
            }
        });

        Schema::table('invoices', function (Blueprint $table) {
            if (Schema::hasColumn('invoices', 'lorry_receipt_id')) {
                $table->dropForeign(['lorry_receipt_id']);
            }
            $columnsToDrop = [];
            foreach (['amount_debit', 'amount_credit', 'amount_debit_date', 'amount_credit_date', 'lorry_receipt_id'] as $col) {
                if (Schema::hasColumn('invoices', $col)) {
                    $columnsToDrop[] = $col;
                }
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
