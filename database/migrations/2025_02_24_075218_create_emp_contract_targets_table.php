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
        Schema::create('emp_contract_targets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('employee_id');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->string('month'); // Month (e.g., "2024-10")

            $table->decimal('base_target', 15, 2)->default(0.00);
            $table->decimal('contract_target', 15, 2)->default(0.00);
            $table->decimal('achieved_target', 15, 2)->default(0.00);
            $table->decimal('cancelled_contract_amt', 15, 2)->default(0.00);
            $table->decimal('remaining_target', 15, 2)->default(0.00);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emp_contract_targets');
    }
};
