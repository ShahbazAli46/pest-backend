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
        Schema::table('vehicles', function (Blueprint $table) {
            $table->string('modal_name')->nullable(); 
            $table->unsignedBigInteger('user_id')->nullable()->comment('assign to employee');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->string('condition')->nullable(); 
            $table->date('expiry_date')->nullable(); 
            $table->string('oil_change_limit',50)->nullable(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicles', function (Blueprint $table) {
            $table->dropForeign(['user_id']); 
            $table->dropColumn('modal_name');
            $table->dropColumn('user_id');
            $table->dropColumn('condition');
            $table->dropColumn('expiry_date');
            $table->dropColumn('oil_change_limit');
        });
    }
};
