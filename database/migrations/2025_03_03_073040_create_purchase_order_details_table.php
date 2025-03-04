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
        Schema::create('purchase_order_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_order_id');
            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('product_id');
            $table->decimal('qty', 15, 2)->default(0.00);
            $table->decimal('price', 15, 2)->default(0.00);
            $table->decimal('sub_total', 15, 2)->default(0.00);
            $table->decimal('vat_per', 15, 2)->default(0.00);
            $table->decimal('vat_amt', 15, 2)->default(0.00);
            $table->decimal('grand_total', 15, 2)->default(0.00);
            $table->enum('status',['pending','approved','rejected'])->default('pending');
            $table->timestamp('status_change_date')->nullable(); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_details');
    }
};
