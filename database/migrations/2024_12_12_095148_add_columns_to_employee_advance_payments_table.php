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
        Schema::table('employee_advance_payments', function (Blueprint $table) {
            $table->string('description')->nullable();
            $table->decimal('received_payment', 15, 2)->default(0.00);
            $table->enum('payment_type',['dr','cr'])->default('cr');
            $table->decimal('balance', 15, 2)->default(0.00);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_advance_payments', function (Blueprint $table) {
            $table->dropColumn('description');
            $table->dropColumn('received_payment');
            $table->dropColumn('payment_type');
            $table->dropColumn('balance');
        });
    }
};
