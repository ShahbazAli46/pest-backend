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
        Schema::create('quote_services', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quote_id');
            $table->unsignedBigInteger('service_id');
            $table->integer('no_of_services')->default(1);
            $table->enum('job_type',['one_time','yearly','monthly','daily','weekly','custom'])->nullable();
            $table->decimal('rate', 15, 2)->default(0);
            $table->decimal('sub_total', 15, 2)->default(0);
            // $table->boolean('is_extra_service')->default(0);            
            $table->foreign('quote_id')->references('id')->on('quotes')->onDelete('cascade');
            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quote_services');
    }
};
