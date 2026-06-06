<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lorry_party_profiles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('company_id')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('type');

            $table->string('code')->nullable();
            $table->string('name')->nullable();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('financer_name')->nullable();
            $table->text('financer_address')->nullable();

            $table->string('place')->nullable();
            $table->string('licence_no')->nullable();
            $table->string('licence_date')->nullable();
            $table->string('licence_issued_by')->nullable();
            $table->text('rto_address')->nullable();
            $table->string('valid_up_to')->nullable();
            $table->string('advice_no')->nullable();
            $table->string('advice_date')->nullable();
            $table->string('destination_broker_name')->nullable();
            $table->text('destination_broker_address')->nullable();

            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
            $table->unique(['company_id', 'customer_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lorry_party_profiles');
    }
};
