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
        Schema::table('companies', function (Blueprint $table) {
            $table->string('gstin')->nullable()->after('tax_id');
            $table->string('pan_no')->nullable()->after('gstin');
            $table->string('tagline')->nullable()->after('pan_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn(['gstin', 'pan_no', 'tagline']);
        });
    }
};
