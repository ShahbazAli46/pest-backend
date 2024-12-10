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
        Schema::create('employee_docs', function (Blueprint $table) {
            $table->id();
            $table->string('name',100);
            $table->string('file')->nullable();
            $table->string('status',100)->nullable();
            $table->date('start')->nullable();
            $table->date('expiry')->nullable();
            $table->string('desc')->nullable();
            $table->unsignedBigInteger('employee_user_id');
            $table->foreign('employee_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('employee_id');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_docs');
    }
};
