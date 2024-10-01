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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('supplier_name');
            $table->string('company_name')->nullable();
            $table->string('email')->unique();
            $table->string('number',100)->nullable();
            $table->string('trn_no')->nullable();
            $table->string('item_notes')->nullable();
            $table->string('address')->nullable();
            $table->string('country',100)->nullable();
            $table->string('state',100)->nullable();
            $table->string('city',100)->nullable();
            $table->string('zip',100)->nullable();
            $table->string('tag')->nullable();
            $table->decimal('opening_balance', 15, 2)->default(0.00);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
