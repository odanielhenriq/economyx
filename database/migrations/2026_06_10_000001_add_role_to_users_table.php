<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('user')->after('email');
            $table->index('role');
        });

        DB::table('users')
            ->where('email', 'daniel.henrique00@hotmail.com')
            ->update(['role' => 'dev']);

        DB::table('users')
            ->where('email', '!=', 'daniel.henrique00@hotmail.com')
            ->update(['role' => 'user']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropColumn('role');
        });
    }
};
