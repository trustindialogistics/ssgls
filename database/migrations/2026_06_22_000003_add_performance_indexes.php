<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // customers: frequently queried by company_id, type, and name for listings
        Schema::table('customers', function (Blueprint $table) {
            if ($this->indexDoesNotExist('customers', 'customers_company_type_idx')) {
                $table->index(['company_id', 'type'], 'customers_company_type_idx');
            }
            if ($this->indexDoesNotExist('customers', 'customers_company_name_idx')) {
                $table->index(['company_id', 'name'], 'customers_company_name_idx');
            }
            if ($this->indexDoesNotExist('customers', 'customers_company_created_idx')) {
                $table->index(['company_id', 'created_at'], 'customers_company_created_idx');
            }
        });

        // estimates: queried by company_id, status, customer_id for listings
        Schema::table('estimates', function (Blueprint $table) {
            if ($this->indexDoesNotExist('estimates', 'estimates_company_status_idx')) {
                $table->index(['company_id', 'status'], 'estimates_company_status_idx');
            }
            if ($this->indexDoesNotExist('estimates', 'estimates_company_customer_idx')) {
                $table->index(['company_id', 'customer_id'], 'estimates_company_customer_idx');
            }
            if ($this->indexDoesNotExist('estimates', 'estimates_company_date_idx')) {
                $table->index(['company_id', 'estimate_date'], 'estimates_company_date_idx');
            }
        });

        // payments: frequently queried by company_id, customer_id, payment_date
        Schema::table('payments', function (Blueprint $table) {
            if ($this->indexDoesNotExist('payments', 'payments_company_customer_idx')) {
                $table->index(['company_id', 'customer_id'], 'payments_company_customer_idx');
            }
            if ($this->indexDoesNotExist('payments', 'payments_company_date_idx')) {
                $table->index(['company_id', 'payment_date'], 'payments_company_date_idx');
            }
            if ($this->indexDoesNotExist('payments', 'payments_company_invoice_idx')) {
                $table->index(['company_id', 'invoice_id'], 'payments_company_invoice_idx');
            }
        });

        // recurring_invoices: queried by company_id, status, customer_id
        Schema::table('recurring_invoices', function (Blueprint $table) {
            if ($this->indexDoesNotExist('recurring_invoices', 'recurring_inv_company_status_idx')) {
                $table->index(['company_id', 'status'], 'recurring_inv_company_status_idx');
            }
            if ($this->indexDoesNotExist('recurring_invoices', 'recurring_inv_company_customer_idx')) {
                $table->index(['company_id', 'customer_id'], 'recurring_inv_company_customer_idx');
            }
        });

        // items: queried by company_id, name for listings
        Schema::table('items', function (Blueprint $table) {
            if ($this->indexDoesNotExist('items', 'items_company_name_idx')) {
                $table->index(['company_id', 'name'], 'items_company_name_idx');
            }
            if ($this->indexDoesNotExist('items', 'items_company_created_idx')) {
                $table->index(['company_id', 'created_at'], 'items_company_created_idx');
            }
        });

        // transport_invoices: queried by company_id, customer_id, invoice_date
        Schema::table('transport_invoices', function (Blueprint $table) {
            if ($this->indexDoesNotExist('transport_invoices', 'transport_inv_company_customer_idx')) {
                $table->index(['company_id', 'customer_id'], 'transport_inv_company_customer_idx');
            }
            if ($this->indexDoesNotExist('transport_invoices', 'transport_inv_company_date_idx')) {
                $table->index(['company_id', 'invoice_date'], 'transport_inv_company_date_idx');
            }
        });

        // expense_categories: queried by company_id for listings
        Schema::table('expense_categories', function (Blueprint $table) {
            if ($this->indexDoesNotExist('expense_categories', 'expense_cat_company_name_idx')) {
                $table->index(['company_id', 'name'], 'expense_cat_company_name_idx');
            }
        });

        // lorry_party_profiles: already has (company_id, type, name) - add created_at for sorting
        Schema::table('lorry_party_profiles', function (Blueprint $table) {
            if ($this->indexDoesNotExist('lorry_party_profiles', 'lpp_company_created_idx')) {
                $table->index(['company_id', 'created_at'], 'lpp_company_created_idx');
            }
        });

        // notes: queried by company_id, type
        Schema::table('notes', function (Blueprint $table) {
            if ($this->indexDoesNotExist('notes', 'notes_company_type_idx')) {
                $table->index(['company_id', 'type'], 'notes_company_type_idx');
            }
        });

        // payment_methods: queried by company_id
        Schema::table('payment_methods', function (Blueprint $table) {
            if ($this->indexDoesNotExist('payment_methods', 'payment_methods_company_name_idx')) {
                $table->index(['company_id', 'name'], 'payment_methods_company_name_idx');
            }
        });

        // units: queried by company_id
        Schema::table('units', function (Blueprint $table) {
            if ($this->indexDoesNotExist('units', 'units_company_name_idx')) {
                $table->index(['company_id', 'name'], 'units_company_name_idx');
            }
        });

        // tax_types: queried by company_id
        Schema::table('tax_types', function (Blueprint $table) {
            if ($this->indexDoesNotExist('tax_types', 'tax_types_company_name_idx')) {
                $table->index(['company_id', 'name'], 'tax_types_company_name_idx');
            }
        });

        // creator_id and updated_by indexes for audit trail queries
        Schema::table('invoices', function (Blueprint $table) {
            if ($this->indexDoesNotExist('invoices', 'invoices_creator_id_idx')) {
                $table->index('creator_id', 'invoices_creator_id_idx');
            }
            if ($this->indexDoesNotExist('invoices', 'invoices_updated_by_idx')) {
                $table->index('updated_by', 'invoices_updated_by_idx');
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            if ($this->indexDoesNotExist('payments', 'payments_creator_id_idx')) {
                $table->index('creator_id', 'payments_creator_id_idx');
            }
            if ($this->indexDoesNotExist('payments', 'payments_updated_by_idx')) {
                $table->index('updated_by', 'payments_updated_by_idx');
            }
        });

        Schema::table('expenses', function (Blueprint $table) {
            if ($this->indexDoesNotExist('expenses', 'expenses_creator_id_idx')) {
                $table->index('creator_id', 'expenses_creator_id_idx');
            }
            if ($this->indexDoesNotExist('expenses', 'expenses_updated_by_idx')) {
                $table->index('updated_by', 'expenses_updated_by_idx');
            }
        });

        Schema::table('lorry_receipts', function (Blueprint $table) {
            if ($this->indexDoesNotExist('lorry_receipts', 'lorry_receipts_creator_id_idx')) {
                $table->index('creator_id', 'lorry_receipts_creator_id_idx');
            }
            if ($this->indexDoesNotExist('lorry_receipts', 'lorry_receipts_updated_by_idx')) {
                $table->index('updated_by', 'lorry_receipts_updated_by_idx');
            }
        });
    }

    public function down(): void
    {
        // Drop all new indexes safely with existence checks
        $this->dropIndexIfExists('customers', 'customers_company_type_idx');
        $this->dropIndexIfExists('customers', 'customers_company_name_idx');
        $this->dropIndexIfExists('customers', 'customers_company_created_idx');

        $this->dropIndexIfExists('estimates', 'estimates_company_status_idx');
        $this->dropIndexIfExists('estimates', 'estimates_company_customer_idx');
        $this->dropIndexIfExists('estimates', 'estimates_company_date_idx');

        $this->dropIndexIfExists('payments', 'payments_company_customer_idx');
        $this->dropIndexIfExists('payments', 'payments_company_date_idx');
        $this->dropIndexIfExists('payments', 'payments_company_invoice_idx');

        $this->dropIndexIfExists('recurring_invoices', 'recurring_inv_company_status_idx');
        $this->dropIndexIfExists('recurring_invoices', 'recurring_inv_company_customer_idx');

        $this->dropIndexIfExists('items', 'items_company_name_idx');
        $this->dropIndexIfExists('items', 'items_company_created_idx');

        $this->dropIndexIfExists('transport_invoices', 'transport_inv_company_customer_idx');
        $this->dropIndexIfExists('transport_invoices', 'transport_inv_company_date_idx');

        $this->dropIndexIfExists('expense_categories', 'expense_cat_company_name_idx');

        $this->dropIndexIfExists('lorry_party_profiles', 'lpp_company_created_idx');

        $this->dropIndexIfExists('notes', 'notes_company_type_idx');

        $this->dropIndexIfExists('payment_methods', 'payment_methods_company_name_idx');

        $this->dropIndexIfExists('units', 'units_company_name_idx');

        $this->dropIndexIfExists('tax_types', 'tax_types_company_name_idx');

        $this->dropIndexIfExists('invoices', 'invoices_creator_id_idx');
        $this->dropIndexIfExists('invoices', 'invoices_updated_by_idx');

        $this->dropIndexIfExists('payments', 'payments_creator_id_idx');
        $this->dropIndexIfExists('payments', 'payments_updated_by_idx');

        $this->dropIndexIfExists('expenses', 'expenses_creator_id_idx');
        $this->dropIndexIfExists('expenses', 'expenses_updated_by_idx');

        $this->dropIndexIfExists('lorry_receipts', 'lorry_receipts_creator_id_idx');
        $this->dropIndexIfExists('lorry_receipts', 'lorry_receipts_updated_by_idx');
    }

    /**
     * Check if an index exists on a table using Schema manager
     */
    private function indexDoesNotExist(string $table, string $indexName): bool
    {
        try {
            $indexes = Schema::getIndexes($table);
            foreach ($indexes as $idx) {
                if ($idx['name'] === $indexName) {
                    return false;
                }
            }
            return true;
        } catch (\Exception $e) {
            return true;
        }
    }

    /**
     * Drop an index if it exists, safely handling errors
     */
    private function dropIndexIfExists(string $table, string $indexName): void
    {
        try {
            $indexes = Schema::getIndexes($table);
            $exists = false;
            foreach ($indexes as $idx) {
                if ($idx['name'] === $indexName) {
                    $exists = true;
                    break;
                }
            }
            
            if ($exists) {
                Schema::table($table, function (Blueprint $table) use ($indexName) {
                    $table->dropIndex($indexName);
                });
            }
        } catch (\Exception $e) {
            \Log::debug('Could not drop index ' . $indexName . ' on ' . $table . ': ' . $e->getMessage());
        }
    }
};
