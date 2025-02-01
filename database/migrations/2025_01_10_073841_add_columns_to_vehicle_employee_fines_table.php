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
        Schema::table('vehicle_employee_fines', function (Blueprint $table) {
            $table->string('month'); // Month (e.g., "2024-10")
            $table->string('description')->nullable();
            $table->decimal('fine_received', 15, 2)->default(0.00);
            $table->enum('entry_type',['dr','cr'])->default('cr');
            $table->decimal('balance', 15, 2)->default(0.00);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vehicle_employee_fines', function (Blueprint $table) {
            $table->dropColumn('month');
            $table->dropColumn('description');
            $table->dropColumn('fine_received');
            $table->dropColumn('entry_type');
            $table->dropColumn('balance');
        });
    }
};
