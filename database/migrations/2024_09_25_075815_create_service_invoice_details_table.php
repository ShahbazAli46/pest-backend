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
        Schema::create('service_invoice_details', function (Blueprint $table) {
            $table->id();
            $table->morphs('itemable');
            $table->unsignedBigInteger('service_invoice_id');
            $table->foreign('service_invoice_id')->references('id')->on('service_invoices')->onDelete('cascade');
            $table->string('job_type',50)->nullable();
            $table->decimal('rate', 15, 2)->default(0);
            $table->decimal('sub_total', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_invoice_details');
    }
};
