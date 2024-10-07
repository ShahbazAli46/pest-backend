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
        Schema::create('banks_info', function (Blueprint $table) {
            $table->id();
            $table->string('bank_name',100)->nullable();
            $table->string('iban',100)->nullable();
            $table->string('account_number',100)->nullable();
            $table->string('address',255)->nullable();
            $table->unsignedBigInteger('linkable_id'); // Morph id
            $table->string('linkable_type', 191); // Limit to 191 characters
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('banks_info');
    }
};
