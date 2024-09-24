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
        Schema::create('job_service_report_areas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_id');
            $table->foreign('job_id')->references('id')->on('jobs')->onDelete('cascade');
            $table->unsignedBigInteger('job_service_report_id');
            $table->foreign('job_service_report_id')->references('id')->on('job_service_reports')->onDelete('cascade');
            $table->string('inspected_areas')->nullable();
            $table->string('manifested_areas')->nullable();
            $table->string('report_and_follow_up_detail')->nullable();
            $table->string('infestation_level')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_service_report_areas');
    }
};
