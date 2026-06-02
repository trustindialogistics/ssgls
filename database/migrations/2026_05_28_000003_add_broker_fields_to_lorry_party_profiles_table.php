<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lorry_party_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('lorry_party_profiles', 'advice_no')) {
                $table->string('advice_no')->nullable()->after('valid_up_to');
            }

            if (! Schema::hasColumn('lorry_party_profiles', 'advice_date')) {
                $table->string('advice_date')->nullable()->after('advice_no');
            }

            if (! Schema::hasColumn('lorry_party_profiles', 'destination_broker_name')) {
                $table->string('destination_broker_name')->nullable()->after('advice_date');
            }

            if (! Schema::hasColumn('lorry_party_profiles', 'destination_broker_address')) {
                $table->text('destination_broker_address')->nullable()->after('destination_broker_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('lorry_party_profiles', function (Blueprint $table) {
            $columns = [
                'destination_broker_address',
                'destination_broker_name',
                'advice_date',
                'advice_no',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('lorry_party_profiles', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
