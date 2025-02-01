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
        Schema::table('vehicle_employee_fines', function (Blueprint $table) {
            $table->decimal('vat_per', 15, 2)->default(0.00)->after('fine');
            $table->decimal('vat_amount', 15, 2)->default(0.00)->after('vat_per');
            $table->decimal('total_fine', 15, 2)->default(0.00)->after('vat_amount');
            $table->unsignedBigInteger('bank_id')->nullable();
            $table->foreign('bank_id')->references('id')->on('banks')->onDelete('cascade');
            $table->enum('payment_type',['cash','cheque','online','none'])->nullable();
            $table->decimal('cash_amt', 15, 2)->default(0.00);
            $table->decimal('cheque_amt', 15, 2)->default(0.00);
            $table->decimal('online_amt', 15, 2)->default(0.00);
            $table->string('cheque_no',100)->nullable();
            $table->date('cheque_date')->nullable();
            $table->string('transection_id',100)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {       
        Schema::table('vehicle_employee_fines', function (Blueprint $table) {
            $table->dropForeign(['bank_id']);
            $table->dropColumn('bank_id');
            $table->dropColumn('payment_type');
            $table->dropColumn('cash_amt');
            $table->dropColumn('cheque_amt');
            $table->dropColumn('online_amt');
            $table->dropColumn('cheque_no');
            $table->dropColumn('cheque_date');
            $table->dropColumn('transection_id');
            $table->dropColumn('vat_per');
            $table->dropColumn('vat_amount');
            $table->dropColumn('total_fine');
        });
    }
};
