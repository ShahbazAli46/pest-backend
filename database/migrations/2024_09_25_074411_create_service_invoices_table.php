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
        Schema::create('service_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('service_invoice_id',50)->nullable();
            $table->unsignedBigInteger('invoiceable_id'); // Morph id
            $table->string('invoiceable_type', 191); // Limit to 191 characters
            $table->unsignedBigInteger('user_id')->comment('for client user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->date('issued_date')->nullable();
            // $table->date('due_date')->nullable();
            $table->decimal('total_amt', 10, 2)->default(0);
            $table->decimal('paid_amt', 10, 2)->default(0);
            $table->enum('status', ['paid','unpaid'])->default('unpaid');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_invoices');
    }
};
