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
        Schema::create('service_invoice_amt_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('service_invoice_id');
            $table->foreign('service_invoice_id')->references('id')->on('service_invoices')->onDelete('cascade');
            $table->unsignedBigInteger('user_id')->comment('for client user_id');
            $table->decimal('paid_amt', 15, 2)->default(0);
            $table->decimal('remaining_amt', 15, 2)->default(0);
            $table->string('description',255)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_invoice_amt_history');
    }
};
