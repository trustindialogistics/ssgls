<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transport_invoices', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('company_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();

            // LR / Bill fields
            $table->string('lr_number')->nullable();
            $table->string('branch_code')->nullable();
            $table->date('invoice_date')->nullable();
            $table->date('due_date')->nullable();

            // For PDF URL like other entities
            $table->string('unique_hash')->nullable()->index();

            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transport_invoices');
    }
};

