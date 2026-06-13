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
            $table->index(['template_name', 'company_id'], 'idx_invoices_template_company');
            $table->index(['template_name', 'company_id', 'invoice_number'], 'idx_invoices_template_company_number');
            $table->index('lorry_receipt_id', 'idx_invoices_lorry_receipt_id');
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->index(['consignment_number', 'invoice_id'], 'idx_items_consignment_invoice');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('idx_invoices_template_company');
            $table->dropIndex('idx_invoices_template_company_number');
            $table->dropIndex('idx_invoices_lorry_receipt_id');
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropIndex('idx_items_consignment_invoice');
        });
    }
};
