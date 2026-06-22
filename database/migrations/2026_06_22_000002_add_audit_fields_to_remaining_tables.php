<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // estimates: add both if not exist
        Schema::table('estimates', function (Blueprint $table) {
            if (! Schema::hasColumn('estimates', 'creator_id')) {
                $table->unsignedInteger('creator_id')->nullable()->after('company_id');
                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
            }

            if (! Schema::hasColumn('estimates', 'updated_by')) {
                $table->unsignedInteger('updated_by')->nullable()->after('creator_id');
                $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            }
        });

        // recurring_invoices: add both if not exist
        Schema::table('recurring_invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('recurring_invoices', 'creator_id')) {
                $table->unsignedInteger('creator_id')->nullable()->after('company_id');
                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
            }

            if (! Schema::hasColumn('recurring_invoices', 'updated_by')) {
                $table->unsignedInteger('updated_by')->nullable()->after('creator_id');
                $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            }
        });

        // items: add both if not exist
        Schema::table('items', function (Blueprint $table) {
            if (! Schema::hasColumn('items', 'creator_id')) {
                $table->unsignedInteger('creator_id')->nullable()->after('company_id');
                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
            }

            if (! Schema::hasColumn('items', 'updated_by')) {
                $table->unsignedInteger('updated_by')->nullable()->after('creator_id');
                $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            }
        });

        // customers: add both if not exist
        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'creator_id')) {
                $table->unsignedInteger('creator_id')->nullable()->after('company_id');
                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
            }

            if (! Schema::hasColumn('customers', 'updated_by')) {
                $table->unsignedInteger('updated_by')->nullable()->after('creator_id');
                $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            }
        });

        // expense_categories: add both if not exist
        Schema::table('expense_categories', function (Blueprint $table) {
            if (! Schema::hasColumn('expense_categories', 'creator_id')) {
                $table->unsignedInteger('creator_id')->nullable()->after('company_id');
                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
            }

            if (! Schema::hasColumn('expense_categories', 'updated_by')) {
                $table->unsignedInteger('updated_by')->nullable()->after('creator_id');
                $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            }
        });

        // lorry_party_profiles: add both if not exist
        Schema::table('lorry_party_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('lorry_party_profiles', 'creator_id')) {
                $table->unsignedInteger('creator_id')->nullable()->after('company_id');
                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
            }

            if (! Schema::hasColumn('lorry_party_profiles', 'updated_by')) {
                $table->unsignedInteger('updated_by')->nullable()->after('creator_id');
                $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            }
        });

        // transport_invoices: add both if not exist
        Schema::table('transport_invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('transport_invoices', 'creator_id')) {
                $table->unsignedInteger('creator_id')->nullable()->after('company_id');
                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
            }

            if (! Schema::hasColumn('transport_invoices', 'updated_by')) {
                $table->unsignedInteger('updated_by')->nullable()->after('creator_id');
                $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            }
        });

        // transport_invoice_rows: add both if not exist
        Schema::table('transport_invoice_rows', function (Blueprint $table) {
            if (! Schema::hasColumn('transport_invoice_rows', 'creator_id')) {
                $table->unsignedInteger('creator_id')->nullable()->after('transport_invoice_id');
                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
            }

            if (! Schema::hasColumn('transport_invoice_rows', 'updated_by')) {
                $table->unsignedInteger('updated_by')->nullable()->after('creator_id');
                $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            }
        });

        // notes: add both if not exist
        Schema::table('notes', function (Blueprint $table) {
            if (! Schema::hasColumn('notes', 'creator_id')) {
                $table->unsignedInteger('creator_id')->nullable()->after('company_id');
                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
            }

            if (! Schema::hasColumn('notes', 'updated_by')) {
                $table->unsignedInteger('updated_by')->nullable()->after('creator_id');
                $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            }
        });

        // payment_methods: add both if not exist
        Schema::table('payment_methods', function (Blueprint $table) {
            if (! Schema::hasColumn('payment_methods', 'creator_id')) {
                $table->unsignedInteger('creator_id')->nullable()->after('company_id');
                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
            }

            if (! Schema::hasColumn('payment_methods', 'updated_by')) {
                $table->unsignedInteger('updated_by')->nullable()->after('creator_id');
                $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            }
        });

        // units: add both if not exist
        Schema::table('units', function (Blueprint $table) {
            if (! Schema::hasColumn('units', 'creator_id')) {
                $table->unsignedInteger('creator_id')->nullable()->after('company_id');
                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
            }

            if (! Schema::hasColumn('units', 'updated_by')) {
                $table->unsignedInteger('updated_by')->nullable()->after('creator_id');
                $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            }
        });

        // tax_types: add both if not exist
        Schema::table('tax_types', function (Blueprint $table) {
            if (! Schema::hasColumn('tax_types', 'creator_id')) {
                $table->unsignedInteger('creator_id')->nullable()->after('company_id');
                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
            }

            if (! Schema::hasColumn('tax_types', 'updated_by')) {
                $table->unsignedInteger('updated_by')->nullable()->after('creator_id');
                $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('estimates', function (Blueprint $table) {
            $table->dropForeign(['updated_by']);
            $table->dropForeign(['creator_id']);
            $table->dropColumn(['creator_id', 'updated_by']);
        });

        Schema::table('recurring_invoices', function (Blueprint $table) {
            $table->dropForeign(['updated_by']);
            $table->dropForeign(['creator_id']);
            $table->dropColumn(['creator_id', 'updated_by']);
        });

        Schema::table('items', function (Blueprint $table) {
            $table->dropForeign(['updated_by']);
            $table->dropColumn('updated_by');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropForeign(['updated_by']);
            $table->dropColumn('updated_by');
        });

        Schema::table('expense_categories', function (Blueprint $table) {
            $table->dropForeign(['updated_by']);
            $table->dropForeign(['creator_id']);
            $table->dropColumn(['creator_id', 'updated_by']);
        });

        Schema::table('lorry_party_profiles', function (Blueprint $table) {
            $table->dropForeign(['updated_by']);
            $table->dropForeign(['creator_id']);
            $table->dropColumn(['creator_id', 'updated_by']);
        });

        Schema::table('transport_invoices', function (Blueprint $table) {
            $table->dropForeign(['updated_by']);
            $table->dropForeign(['creator_id']);
            $table->dropColumn(['creator_id', 'updated_by']);
        });

        Schema::table('transport_invoice_rows', function (Blueprint $table) {
            $table->dropForeign(['updated_by']);
            $table->dropForeign(['creator_id']);
            $table->dropColumn(['creator_id', 'updated_by']);
        });

        Schema::table('notes', function (Blueprint $table) {
            $table->dropForeign(['updated_by']);
            $table->dropForeign(['creator_id']);
            $table->dropColumn(['creator_id', 'updated_by']);
        });

        Schema::table('payment_methods', function (Blueprint $table) {
            $table->dropForeign(['updated_by']);
            $table->dropForeign(['creator_id']);
            $table->dropColumn(['creator_id', 'updated_by']);
        });

        Schema::table('units', function (Blueprint $table) {
            $table->dropForeign(['updated_by']);
            $table->dropForeign(['creator_id']);
            $table->dropColumn(['creator_id', 'updated_by']);
        });

        Schema::table('tax_types', function (Blueprint $table) {
            $table->dropForeign(['updated_by']);
            $table->dropForeign(['creator_id']);
            $table->dropColumn(['creator_id', 'updated_by']);
        });
    }
};
