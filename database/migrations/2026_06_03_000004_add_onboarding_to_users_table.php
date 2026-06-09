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
            $table->timestamp('onboarding_completed_at')->nullable()->after('email_verified_at');
            $table->string('onboarding_step')->nullable()->after('onboarding_completed_at');
        });

        // Usuários existentes com transações já completaram onboarding
        DB::table('users')
            ->whereIn('id', function ($query) {
                $query->select('user_id')->from('transaction_user');
            })
            ->update([
                'onboarding_completed_at' => now(),
                'onboarding_step' => 'done',
            ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['onboarding_completed_at', 'onboarding_step']);
        });
    }
};
