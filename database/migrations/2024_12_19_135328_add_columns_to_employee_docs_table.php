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
        Schema::table('employee_docs', function (Blueprint $table) {
            $table->date('process_date')->nullable();
            $table->decimal('process_amt', 15, 2)->default(0.00);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_docs', function (Blueprint $table) {
            $table->dropColumn('process_date');
            $table->dropColumn('process_amt');
        });
    }
};
