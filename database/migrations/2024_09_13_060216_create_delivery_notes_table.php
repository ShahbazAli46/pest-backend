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
        Schema::create('delivery_notes', function (Blueprint $table) {
            $table->id();
            $table->string('dn_id',50)->nullable();
            $table->string('purchase_invoice')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('cascade');
            $table->string('city',100)->nullable();
            $table->string('zip',100)->nullable();
            $table->date('order_date')->nullable();
            $table->date('delivery_date')->nullable();
            $table->text('private_note')->nullable();
            $table->decimal('sub_total', 15, 2)->default(0.00);
            $table->decimal('vat_amt', 15, 2)->default(0.00);
            $table->decimal('dis_per', 15, 2)->default(0.00);
            $table->decimal('dis_amt', 15, 2)->default(0.00);
            $table->decimal('grand_total', 15, 2)->default(0.00);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_notes');
    }
};
