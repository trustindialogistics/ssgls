<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->unsignedBigInteger('payment_id')->nullable()->after('payment_method_id');
            $table->unsignedInteger('invoice_id')->nullable()->after('payment_id');
            $table->boolean('auto_generated')->default(false)->after('invoice_id');

            $table->index('payment_id');
            $table->index('invoice_id');
            $table->index('auto_generated');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex(['payment_id']);
            $table->dropIndex(['invoice_id']);
            $table->dropIndex(['auto_generated']);
            $table->dropColumn(['payment_id', 'invoice_id', 'auto_generated']);
        });
    }
};
