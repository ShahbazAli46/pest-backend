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
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('for client user_id');
            $table->string('quote_title')->nullable();
            $table->unsignedBigInteger('client_address_id');
            $table->foreign('client_address_id')->references('id')->on('client_addresses')->onDelete('cascade');
            $table->string('subject')->nullable();
            $table->json('service_ids')->nullable();
            $table->json('tm_ids')->nullable();
            $table->text('description')->nullable();
            $table->string('trn')->nullable();
            $table->string('tag')->nullable();
            $table->integer('duration_in_months')->default(1);
            $table->boolean('is_food_watch_account')->default(0);
            $table->enum('billing_method',['installments','monthly','service','one_time'])->nullable();
            $table->integer('no_of_installments')->default(1);

            $table->decimal('sub_total', 15, 2)->default(0);
            $table->decimal('dis_per', 15, 2)->default(0);
            $table->decimal('dis_amt', 15, 2)->default(0);
            $table->decimal('vat_per', 15, 2)->default(0);
            $table->decimal('vat_amt', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);

            $table->date('contract_start_date')->nullable();
            $table->date('contract_end_date')->nullable();
            $table->boolean('is_contracted')->default(0);
            $table->unsignedBigInteger('term_and_condition_id');
            $table->foreign('term_and_condition_id')->references('id')->on('terms_and_conditions')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotes');
    }
};
