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
        foreach (['invoices', 'lorry_receipts'] as $table) {
            Schema::table($table, function (Blueprint $tableGroup) use ($table) {
                if (!Schema::hasColumn($table, 'date_created')) {
                    $tableGroup->dateTime('date_created')->nullable();
                }
                if (!Schema::hasColumn($table, 'date_modified')) {
                    $tableGroup->dateTime('date_modified')->nullable();
                }
                if (!Schema::hasColumn($table, 'modified_dates')) {
                    $tableGroup->json('modified_dates')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach (['invoices', 'lorry_receipts'] as $table) {
            Schema::table($table, function (Blueprint $tableGroup) use ($table) {
                $columnsToDrop = [];
                foreach (['date_created', 'date_modified', 'modified_dates'] as $col) {
                    if (Schema::hasColumn($table, $col)) {
                        $columnsToDrop[] = $col;
                    }
                }
                if (!empty($columnsToDrop)) {
                    $tableGroup->dropColumn($columnsToDrop);
                }
            });
        }
    }
};
