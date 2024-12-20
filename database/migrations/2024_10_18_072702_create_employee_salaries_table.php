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
        Schema::create('employee_salaries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('user_id'); 
            $table->decimal('basic_salary', 15, 2)->default(0.00);
            $table->decimal('allowance', 15, 2)->default(0.00);
            $table->decimal('other', 15, 2)->default(0.00);
            $table->decimal('total_salary', 15, 2)->default(0.00);
            $table->decimal('adv_paid', 15, 2)->default(0.00);
            $table->decimal('paid_salary', 15, 2)->default(0.00);
            $table->string('month'); // Month (e.g., "2024-10")
            $table->decimal('attendance_per', 15, 2)->default(0.00);
            $table->enum('status', ['paid', 'unpaid'])->default('unpaid'); // Payment status
            $table->timestamp('paid_at')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_salaries');
    }
};

