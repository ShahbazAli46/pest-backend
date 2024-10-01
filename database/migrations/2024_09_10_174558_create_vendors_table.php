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
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('contact',100)->nullable();
            $table->string('firm_name')->nullable();
            $table->string('mng_name')->nullable();
            $table->string('mng_contact')->nullable();
            $table->string('mng_email')->nullable();
            $table->string('acc_name')->nullable();
            $table->string('acc_contact')->nullable();
            $table->string('acc_email')->nullable();
            $table->decimal('percentage')->nullable();
            $table->string('tag')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
