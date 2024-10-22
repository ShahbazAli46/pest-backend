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
        Schema::create('employee_commissions', function (Blueprint $table) {
            $table->id();
            $table->decimal('target', 15, 2)->default(0.00);
            $table->decimal('commission_per', 15, 2)->default(0.00);
            $table->decimal('sale', 15, 2)->default(0.00);
            $table->decimal('paid_amt', 15, 2)->default(0.00);
            $table->string('month'); // Month (e.g., "2024-10")
            $table->enum('status', ['paid', 'unpaid'])->default('unpaid'); // Payment status
            $table->timestamp('paid_at')->nullable();
            $table->unsignedBigInteger('referencable_id'); // Morph id
            $table->string('referencable_type', 191); // Limit to 191 characters
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_commissions');
    }
};
