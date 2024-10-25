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
        Schema::table('employees', function (Blueprint $table) {
            $table->string('labour_card_expiry',50)->nullable(); 
            $table->decimal('commission_per', 15, 2)->default(0.00);
        });
    }


    //labour card expiry date
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('labour_card_expiry');
            $table->dropColumn('commission_per');
        });
    }
};