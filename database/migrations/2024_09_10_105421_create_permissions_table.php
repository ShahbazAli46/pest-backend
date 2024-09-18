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
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name',100);
            $table->string('icon')->nullable();
            $table->string('api_url',100)->unique();
            $table->string('frontend_url',100)->unique();
            $table->boolean('is_main')->default(0);
            $table->foreignId('parent_id')->nullable()->constrained('permissions')->onDelete('set null');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
