<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdatePermissionsTable extends Migration
{
    public function up()
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn('parent_id');

            $table->string('parent_api_url', 255)->nullable();
            $table->foreign('parent_api_url')->references('api_url')->on('permissions')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropForeign(['parent_api_url']);
            $table->dropColumn('parent_api_url');

            $table->foreignId('parent_id')->nullable()->constrained('permissions')->onDelete('set null');
        });
    }
}
