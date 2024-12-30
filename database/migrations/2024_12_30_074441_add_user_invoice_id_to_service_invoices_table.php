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
        Schema::table('service_invoices', function (Blueprint $table) {
            $table->string('user_invoice_id',50)->after('service_invoice_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_invoices', function (Blueprint $table) {
            $table->dropColumn('user_invoice_id');
        });
    }
};
