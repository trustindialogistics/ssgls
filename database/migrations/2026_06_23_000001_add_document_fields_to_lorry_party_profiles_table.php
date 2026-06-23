<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lorry_party_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('lorry_party_profiles', 'rc_front_path')) {
                $table->string('rc_front_path')->nullable()->after('financer_address');
            }
            if (! Schema::hasColumn('lorry_party_profiles', 'rc_back_path')) {
                $table->string('rc_back_path')->nullable()->after('rc_front_path');
            }
            if (! Schema::hasColumn('lorry_party_profiles', 'pan_front_path')) {
                $table->string('pan_front_path')->nullable()->after('rc_back_path');
            }
            if (! Schema::hasColumn('lorry_party_profiles', 'insurance_path')) {
                $table->string('insurance_path')->nullable()->after('pan_front_path');
            }
            if (! Schema::hasColumn('lorry_party_profiles', 'license_front_path')) {
                $table->string('license_front_path')->nullable()->after('insurance_path');
            }
            if (! Schema::hasColumn('lorry_party_profiles', 'license_back_path')) {
                $table->string('license_back_path')->nullable()->after('license_front_path');
            }
            if (! Schema::hasColumn('lorry_party_profiles', 'pan_front_path_broker')) {
                $table->string('pan_front_path_broker')->nullable()->after('license_back_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('lorry_party_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'rc_front_path', 'rc_back_path', 'pan_front_path', 'insurance_path',
                'license_front_path', 'license_back_path', 'pan_front_path_broker',
            ]);
        });
    }
};
