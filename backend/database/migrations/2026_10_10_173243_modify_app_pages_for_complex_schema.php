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
        Schema::table('app_pages', function (Blueprint $table) {
            $table->dropForeign(['dynamic_table_id']);
            $table->dropColumn('dynamic_table_id');
            $table->dropColumn('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_pages', function (Blueprint $table) {
            //
        });
    }
};
