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
        Schema::table('employee_salaries', function (Blueprint $table) {
            $table->decimal('payable_salary', 15, 2)->default(0.00)->after('adv_paid')->comment('after fine and per % calculation');
            $table->enum('transection_type',['wps','cash'])->nullable();
            $table->decimal('remaining_salary', 15, 2)->default(0.00)->after('adv_received');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_salaries', function (Blueprint $table) {
            $table->dropColumn('payable_salary');
            $table->dropColumn('transection_type');
            $table->dropColumn('remaining_salary');
        });
    }
};
