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
            if (! Schema::hasColumn('companies', 'enrollment_no')) {
                $table->string('enrollment_no')->nullable()->after('gstin');
            }

            if (! Schema::hasColumn('companies', 'billing_branch_name_address')) {
                $table->text('billing_branch_name_address')->nullable()->after('tagline');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (Schema::hasColumn('companies', 'billing_branch_name_address')) {
                $table->dropColumn('billing_branch_name_address');
            }

            if (Schema::hasColumn('companies', 'enrollment_no')) {
                $table->dropColumn('enrollment_no');
            }
        });
    }
};
