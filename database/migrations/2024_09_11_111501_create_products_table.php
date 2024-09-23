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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('product_name')->nullable();
            $table->string('batch_number',100)->nullable();
            $table->unsignedBigInteger('brand_id');
            $table->date('mfg_date')->nullable();
            $table->date('exp_date')->nullable();
            $table->enum('product_type',['Liquid','Powder','Gel','Pieces'])->nullable();
            $table->string('unit',50)->nullable();
            $table->string('active_ingredients')->nullable();
            $table->string('others_ingredients')->nullable();
            $table->string('moccae_approval')->nullable();
            $table->date('moccae_strat_date')->nullable();
            $table->date('moccae_exp_date')->nullable();
            $table->decimal('per_item_qty', 15, 2)->default(0.00);
            $table->text('description')->nullable();
            $table->string('product_picture')->nullable();
            $table->decimal('vat', 15, 2)->default(0.00);
            $table->foreign('brand_id')->references('id')->on('brands')->onDelete('cascade');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
