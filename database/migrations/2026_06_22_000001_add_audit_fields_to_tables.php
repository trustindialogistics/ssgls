<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // lorry_receipts: add both creator_id and updated_by if not exist
        Schema::table('lorry_receipts', function (Blueprint $table) {
            if (! Schema::hasColumn('lorry_receipts', 'creator_id')) {
                $table->unsignedInteger('creator_id')->nullable()->after('company_id');
                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
            }

            if (! Schema::hasColumn('lorry_receipts', 'updated_by')) {
                $table->unsignedInteger('updated_by')->nullable()->after('creator_id');
                $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            }
        });

        // invoices: add updated_by if not exist
        Schema::table('invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('invoices', 'updated_by')) {
                $table->unsignedInteger('updated_by')->nullable()->after('creator_id');
                $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            }
        });

        // payments: add updated_by if not exist
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'updated_by')) {
                $table->unsignedInteger('updated_by')->nullable()->after('creator_id');
                $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            }
        });

        // expenses: add updated_by if not exist
        Schema::table('expenses', function (Blueprint $table) {
            if (! Schema::hasColumn('expenses', 'updated_by')) {
                $table->unsignedInteger('updated_by')->nullable()->after('creator_id');
                $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('lorry_receipts', function (Blueprint $table) {
            $table->dropForeign(['updated_by']);
            $table->dropForeign(['creator_id']);
            $table->dropColumn(['creator_id', 'updated_by']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropForeign(['updated_by']);
            $table->dropColumn('updated_by');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['updated_by']);
            $table->dropColumn('updated_by');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['updated_by']);
            $table->dropColumn('updated_by');
        });
    }
};
