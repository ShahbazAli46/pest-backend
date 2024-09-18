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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('role_id');
            $table->string('profile_image')->nullable();
            $table->string('phone_number',100)->nullable();
            $table->string('eid_no')->nullable();
            $table->decimal('target', 15, 2)->default(0.00);
            $table->date('eid_start')->nullable();
            $table->date('eid_expiry')->nullable();
            $table->string('profession')->nullable();
            $table->string('passport_no')->nullable();
            $table->date('passport_start')->nullable();
            $table->date('passport_expiry')->nullable();

            $table->string('hi_status')->nullable();
            $table->date('hi_start')->nullable();
            $table->date('hi_expiry')->nullable();
            $table->string('ui_status')->nullable();
            $table->date('ui_start')->nullable();
            $table->date('ui_expiry')->nullable();
            $table->string('dm_card')->nullable();
            $table->date('dm_start')->nullable();
            $table->date('dm_expiry')->nullable();

            $table->string('relative_name')->nullable();
            $table->string('relation')->nullable();
            $table->string('emergency_contact')->nullable();
            $table->decimal('basic_salary', 15, 2)->default(0.00);
            $table->decimal('allowance', 15, 2)->default(0.00);
            $table->decimal('other', 15, 2)->default(0.00);
            $table->decimal('total_salary', 15, 2)->default(0.00);

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
