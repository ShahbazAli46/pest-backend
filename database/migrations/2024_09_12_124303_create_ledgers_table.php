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
        Schema::create('ledgers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bank_id')->nullable();
            $table->foreign('bank_id')->references('id')->on('banks')->onDelete('cascade');
            $table->text('description')->nullable();
            $table->decimal('dr_amt', 15, 2)->default(0.00);
            $table->decimal('cr_amt', 15, 2)->default(0.00);
            $table->enum('payment_type',['cash','cheque','online','pos','opening_balance','none'])->nullable();
            $table->decimal('cash_amt', 15, 2)->default(0.00);
            $table->decimal('pos_amt', 15, 2)->default(0.00);
            $table->decimal('cheque_amt', 15, 2)->default(0.00);
            $table->decimal('online_amt', 15, 2)->default(0.00);
            $table->string('cheque_no',100)->nullable();
            $table->date('cheque_date')->nullable();
            $table->enum('entry_type',['dr','cr']);
            $table->string('transection_id',100)->nullable();
            $table->decimal('bank_balance', 15, 2)->default(0.00);
            $table->decimal('cash_balance', 15, 2)->default(0.00);
            $table->unsignedBigInteger('person_id');
            $table->string('person_type');
            $table->unsignedBigInteger('link_id')->nullable();
            $table->enum('link_name',['delivery','supplier_ledger','expense','vehicle_expense','sale','customer_ledger','client_ledger','vehicle_employee_fine','adv_paid','employee_salary'])->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ledgers');
    }
};
