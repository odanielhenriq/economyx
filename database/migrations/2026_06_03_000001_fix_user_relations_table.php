<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('user_relations')) {
            return;
        }

        Schema::table('user_relations', function (Blueprint $table) {
            if (Schema::hasColumn('user_relations', 'transaction_id')) {
                try {
                    $table->dropUnique(['transaction_id', 'user_id']);
                } catch (\Throwable) {
                    // Index may not exist on all drivers.
                }

                $table->dropColumn('transaction_id');
            }
        });
    }

    public function down(): void
    {
        // Irreversible: invalid index was erroneous.
    }
};
