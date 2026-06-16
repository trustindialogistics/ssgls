<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('custom_fields')
            ->where('model_type', 'Invoice')
            ->where('name', 'Received No Of Bilties')
            ->update(['type' => 'Input']);

        DB::table('custom_fields')
            ->where('model_type', 'Invoice')
            ->where('name', 'Received No. of Bilties')
            ->update(['type' => 'Input']);
    }

    public function down(): void
    {
        DB::table('custom_fields')
            ->where('model_type', 'Invoice')
            ->where('name', 'Received No Of Bilties')
            ->update(['type' => 'Number']);

        DB::table('custom_fields')
            ->where('model_type', 'Invoice')
            ->where('name', 'Received No. of Bilties')
            ->update(['type' => 'Number']);
    }
};
