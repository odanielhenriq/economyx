<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaction_user', function (Blueprint $table) {
            $table->boolean('is_settled')->default(false)->after('transaction_id');
            $table->timestamp('settled_at')->nullable()->after('is_settled');
            $table->foreignId('settled_by_user_id')->nullable()->after('settled_at')->constrained('users')->nullOnDelete();
            $table->foreignId('settled_to_user_id')->nullable()->after('settled_by_user_id')->constrained('users')->nullOnDelete();
            $table->string('settlement_note')->nullable()->after('settled_to_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('transaction_user', function (Blueprint $table) {
            $table->dropConstrainedForeignId('settled_to_user_id');
            $table->dropConstrainedForeignId('settled_by_user_id');
            $table->dropColumn(['is_settled', 'settled_at', 'settlement_note']);
        });
    }
};
