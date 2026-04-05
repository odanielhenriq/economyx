<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabela transactions — colunas mais filtradas em queries de dashboard e cashflow
        Schema::table('transactions', function (Blueprint $table) {
            $table->index('due_date');
            $table->index('category_id');
            $table->index('type_id');
        });

        // Tabela transaction_user — pivot N:N, consultada em todo whereHas('users')
        Schema::table('transaction_user', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('transaction_id');
            $table->index(['user_id', 'transaction_id']); // índice composto para lookup do dashboard
        });

        // Tabela transaction_installments — consultada por período e por transação pai
        Schema::table('transaction_installments', function (Blueprint $table) {
            $table->index('due_date');
            $table->index('transaction_id');
        });

        // Tabela credit_card_statements — consultada sempre com (year, month) + credit_card_id
        Schema::table('credit_card_statements', function (Blueprint $table) {
            $table->index(['year', 'month']);
            $table->index('credit_card_id');
        });

        // Tabela recurring_transactions — consultada por data de início nos schedules
        // Nota: não há user_id direto (N:N via recurring_transaction_user) nem next_due_date
        Schema::table('recurring_transactions', function (Blueprint $table) {
            $table->index('start_date');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['due_date']);
            $table->dropIndex(['category_id']);
            $table->dropIndex(['type_id']);
        });

        Schema::table('transaction_user', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['transaction_id']);
            $table->dropIndex(['user_id', 'transaction_id']);
        });

        Schema::table('transaction_installments', function (Blueprint $table) {
            $table->dropIndex(['due_date']);
            $table->dropIndex(['transaction_id']);
        });

        Schema::table('credit_card_statements', function (Blueprint $table) {
            $table->dropIndex(['year', 'month']);
            $table->dropIndex(['credit_card_id']);
        });

        Schema::table('recurring_transactions', function (Blueprint $table) {
            $table->dropIndex(['start_date']);
        });
    }
};
