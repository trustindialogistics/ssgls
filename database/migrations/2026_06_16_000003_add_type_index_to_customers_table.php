<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        try {
            Schema::table('customers', function (Blueprint $table) {
                $table->index('type');
            });
        } catch (\Exception $e) {
            // Index might already exist, ignore
        }
    }

    public function down(): void
    {
        try {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropIndex(['type']);
            });
        } catch (\Exception $e) {
            // Index might not exist or drop failed, ignore
        }
    }
};
