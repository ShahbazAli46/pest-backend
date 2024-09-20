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
        Schema::create('quote_service_dates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quote_id');
            $table->unsignedBigInteger('service_id');
            $table->unsignedBigInteger('quote_service_id');
            $table->date('service_date');
            $table->foreign('quote_id')->references('id')->on('quotes')->onDelete('cascade');
            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
            $table->foreign('quote_service_id')->references('id')->on('quote_services')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quote_service_dates');
    }
};
