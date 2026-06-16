<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Rename "Received No Of Bilties" to "Docket No" in the entry form and order to 0
        DB::table('custom_fields')
            ->where('model_type', 'Invoice')
            ->where('name', 'Received No Of Bilties')
            ->update([
                'label' => 'Docket No',
                'order' => 0,
            ]);

        DB::table('custom_fields')
            ->where('model_type', 'Invoice')
            ->where('name', 'Received No. of Bilties')
            ->update([
                'label' => 'Docket No',
                'order' => 0,
            ]);

        // Rename "Driver Licence Date" to "Issued Dt." in the entry form
        DB::table('custom_fields')
            ->where('model_type', 'Invoice')
            ->where('name', 'Driver Licence Date')
            ->update([
                'label' => 'Issued Dt.',
            ]);
    }

    public function down(): void
    {
        DB::table('custom_fields')
            ->where('model_type', 'Invoice')
            ->where('name', 'Received No Of Bilties')
            ->update([
                'label' => 'Received No Of Bilties',
                'order' => 100,
            ]);

        DB::table('custom_fields')
            ->where('model_type', 'Invoice')
            ->where('name', 'Received No. of Bilties')
            ->update([
                'label' => 'Received No. of Bilties',
                'order' => 100,
            ]);

        DB::table('custom_fields')
            ->where('model_type', 'Invoice')
            ->where('name', 'Driver Licence Date')
            ->update([
                'label' => 'Driver Licence Date',
            ]);
    }
};
