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
        Schema::table('service_invoice_amt_history', function (Blueprint $table) {
            $table->decimal('settlement_amt', 15, 2)->default(0.00);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_invoice_amt_history', function (Blueprint $table) {
            $table->dropColumn(['settlement_amt']);
        });
    }
};
