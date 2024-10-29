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
        Schema::create('received_cash_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_user_id');
            $table->foreign('client_user_id')->references('id')->on('users')->onDelete('cascade');

            $table->unsignedBigInteger('employee_user_id');
            $table->foreign('employee_user_id')->references('id')->on('users')->onDelete('cascade');
            
            $table->unsignedBigInteger('service_invoice_id');
            $table->foreign('service_invoice_id')->references('id')->on('service_invoices')->onDelete('cascade');

            $table->decimal('paid_amt', 15, 2)->default(0);

            $table->unsignedBigInteger('client_ledger_id');
            $table->foreign('client_ledger_id')->references('id')->on('ledgers')->onDelete('cascade');

            $table->enum('status', ['pending','approved','rejected'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('received_cash_records');
    }
};
