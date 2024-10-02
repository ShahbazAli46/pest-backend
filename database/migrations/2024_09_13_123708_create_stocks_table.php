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
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->decimal('total_qty', 15, 2)->default(0.00);
            $table->decimal('stock_in', 15, 2)->default(0.00);
            $table->decimal('stock_out', 15, 2)->default(0.00);
            $table->decimal('remaining_qty', 15, 2)->default(0.00);
            $table->unsignedBigInteger('person_id');
            $table->string('person_type',100)->default('App\Models\User');
            $table->unsignedBigInteger('link_id')->nullable();
            $table->enum('link_name',['purchase_order_detail','assign_stock'])->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock');
    }
};
