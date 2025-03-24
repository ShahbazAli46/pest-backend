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
        Schema::create('imp_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_client_id');
            $table->foreign('user_client_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('job_id');
            $table->foreign('job_id')->references('id')->on('jobs')->onDelete('cascade');
            $table->json('images')->nullable();
            $table->text('description')->nullable();
            $table->date('report_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('imp_reports');
    }
};
