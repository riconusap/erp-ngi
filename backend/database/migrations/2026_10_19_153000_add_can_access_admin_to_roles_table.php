<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->boolean('can_access_admin')->default(false)->after('status');
        });

        // Set can_access_admin = true untuk Super Admin agar tidak terkunci
        DB::table('roles')->where('name', 'Super Admin')->orWhere('id', 1)->update(['can_access_admin' => true]);
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('can_access_admin');
        });
    }
};
