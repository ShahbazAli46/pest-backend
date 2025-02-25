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
        Schema::create('emp_contract_targets_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('emp_contract_target_id');
            $table->foreign('emp_contract_target_id')->references('id')->on('emp_contract_targets')->onDelete('cascade');
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('employee_id');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->string('detail')->nullable();
            $table->string('month'); // Month (e.g., "2024-10")
            $table->unsignedBigInteger('contract_id');
            $table->foreign('contract_id')->references('id')->on('quotes')->onDelete('cascade');
            $table->decimal('amount', 15, 2)->default(0.00);
            $table->enum('type',['add','cancel'])->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emp_contract_targets_details');
    }
};
