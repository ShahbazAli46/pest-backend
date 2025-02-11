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
        Schema::create('advance_cheques', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('bank_id')->nullable();
            $table->foreign('bank_id')->references('id')->on('banks')->onDelete('cascade');
            $table->text('description')->nullable();
            $table->decimal('cheque_amount', 15, 2)->default(0.00);
            $table->string('cheque_no',100)->nullable();
            $table->date('cheque_date')->nullable();

            $table->unsignedBigInteger('linkable_id'); // Morph id
            $table->string('linkable_type', 191); // Limit to 191 characters
            $table->decimal('settlement_amt', 15, 2)->default(0.00);
            
            $table->enum('status',['pending','paid','deferred'])->default('pending');
            $table->timestamp('status_updated_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('advance_cheques');
    }
};
