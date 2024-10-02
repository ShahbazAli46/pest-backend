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
        Schema::table('vendors', function (Blueprint $table) {
            $table->decimal('vat', 15, 2)->default(0.00)->after('tag');
            $table->decimal('opening_balance', 15, 2)->default(0.00)->after('vat');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn('vat');
            $table->dropColumn('opening_balance');
        });
    }
};
