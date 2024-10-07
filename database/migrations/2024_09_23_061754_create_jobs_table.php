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
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('for client user_id');
            $table->string('job_title')->nullable();
            $table->unsignedBigInteger('client_address_id');
            $table->foreign('client_address_id')->references('id')->on('client_addresses')->onDelete('cascade');
            $table->string('subject')->nullable();
            $table->json('service_ids')->nullable();
            $table->json('tm_ids')->nullable();
            $table->text('description')->nullable();
            $table->string('trn')->nullable();
            $table->string('tag')->nullable();
            $table->boolean('is_food_watch_account')->default(0);
            $table->date('job_date')->nullable();
            $table->enum('priority',['high','medium','low']);
            $table->decimal('sub_total', 15, 2)->default(0);
            $table->decimal('dis_per', 15, 2)->default(0);
            $table->decimal('dis_amt', 15, 2)->default(0);
            $table->decimal('vat_per', 15, 2)->default(0);
            $table->decimal('vat_amt', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->boolean('is_completed')->default(0);
            $table->boolean('is_modified')->default(0);

            $table->unsignedBigInteger('captain_id')->nullable();
            $table->foreign('captain_id')->references('id')->on('users')->onDelete('cascade');
            $table->json('team_member_ids')->nullable();
            $table->text('job_instructions')->nullable();
            
            $table->unsignedBigInteger('term_and_condition_id');
            $table->foreign('term_and_condition_id')->references('id')->on('terms_and_conditions')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBigInteger('quote_id')->nullable();
            $table->foreign('quote_id')->references('id')->on('quotes')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs');
    }
};
