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
        Schema::table('advance_cheques', function (Blueprint $table) {
            $table->string('cheque_type',50)->default('receive');//receive || pay
            $table->decimal('vat_per', 15, 2)->default(0.00);
            $table->decimal('vat_amount', 15, 2)->default(0.00)->after('vat_per');
            $table->decimal('cheque_amt_without_vat', 15, 2)->default(0.00);
            $table->json('entry_row_data')->nullable();
            $table->enum('entry_type',['vehicle_expense','expense','supplier_payment'])->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('advance_cheques', function (Blueprint $table) {
            $table->dropColumn(['cheque_type','vat_per','vat_amount','cheque_amt_without_vat','entry_row_data','entry_type']);
        });
    }
};
