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
        Schema::create('renewable_items', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable(); // Name of the item (e.g., "Software License")
            $table->string('type')->nullable(); // Type of item (e.g., "license", "agreement")
            $table->date('start_date')->nullable(); // Expiry date
            $table->date('last_renew_date')->nullable(); // Expiry date
            $table->date('expiry_date')->nullable(); // Expiry date
            $table->boolean('notified')->default(false); // Whether notification has been sent
            $table->text('remarks')->nullable(); // Type of item (e.g., "license", "agreement")
            $table->string('file_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('renewable_items');
    }
};
