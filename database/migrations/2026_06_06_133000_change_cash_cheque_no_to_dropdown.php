<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\CustomField;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        CustomField::query()
            ->where('name', 'Cash/Cheque No.')
            ->where('model_type', 'Invoice')
            ->update([
                'type' => 'Dropdown',
                'options' => [
                    ['name' => 'UPI'],
                    ['name' => 'CHEQUE'],
                    ['name' => 'CASH'],
                    ['name' => 'NET BANKING'],
                ],
                'string_answer' => null,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        CustomField::query()
            ->where('name', 'Cash/Cheque No.')
            ->where('model_type', 'Invoice')
            ->update([
                'type' => 'Input',
                'options' => null,
                'string_answer' => 'CHQ-121',
            ]);
    }
};
