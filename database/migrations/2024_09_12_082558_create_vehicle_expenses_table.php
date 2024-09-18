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
        Schema::create('vehicle_expenses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bank_id')->nullable();
            $table->foreign('bank_id')->references('id')->on('banks')->onDelete('cascade');
            $table->unsignedBigInteger('vehicle_id')->nullable();
            $table->foreign('vehicle_id')->references('id')->on('vehicles')->onDelete('cascade');
            $table->decimal('fuel_amount', 15, 2)->default(0.00);
            $table->decimal('oil_amount', 15, 2)->default(0.00);
            $table->decimal('maintenance_amount', 15, 2)->default(0.00);
            $table->decimal('total_amt', 15, 2)->default(0.00);
            $table->enum('payment_type',['cash','cheque','online']);
            $table->string('cheque_no',100)->nullable();
            $table->date('cheque_date')->nullable();
            $table->string('transection_id',100)->nullable();
            $table->decimal('vat_per', 15, 2)->default(0.00);
            $table->decimal('vat_amount', 15, 2)->default(0.00);
            $table->decimal('total_amount', 15, 2)->default(0.00);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicle_expenses');
    }
};
