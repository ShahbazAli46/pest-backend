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
        Schema::create('service_invoices_assigned_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->unsignedBigInteger('employee_user_id');
            $table->foreign('employee_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('service_invoice_id')->nullable();
            $table->foreign('service_invoice_id')->references('id')->on('service_invoices')->onDelete('cascade');
            $table->string('response_type',100)->nullable();
            $table->date('promise_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_invoices_assigned_history');
    }
};
