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
        Schema::create('inspection_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_client_id');
            $table->foreign('user_client_id')->references('id')->on('users')->onDelete('cascade');

            $table->text('client_remarks')->nullable();
            $table->text('inspection_remarks')->nullable();
            $table->text('recommendation_for_operation')->nullable();
            $table->text('general_comment')->nullable();
            $table->json('pictures')->nullable();
            $table->text('nesting_area')->nullable();

            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
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
        Schema::dropIfExists('inspection_reports');
    }
};
