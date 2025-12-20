<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->date('due_date')->nullable()->after('transaction_date');
            $table->foreignId('recurring_transaction_id')
                ->nullable()
                ->constrained('recurring_transactions')
                ->nullOnDelete()
                ->after('credit_card_id');
        });

        DB::table('transactions')
            ->whereNull('due_date')
            ->update(['due_date' => DB::raw('transaction_date')]);

        $indexName = 'transactions_recurring_due_date_unique';
        $driver = DB::getDriverName();

        if (in_array($driver, ['pgsql', 'sqlite'], true)) {
            try {
                DB::statement(
                    "CREATE UNIQUE INDEX {$indexName} ON transactions (recurring_transaction_id, due_date) " .
                        'WHERE recurring_transaction_id IS NOT NULL'
                );
            } catch (Throwable) {
                Schema::table('transactions', function (Blueprint $table) use ($indexName) {
                    $table->unique(['recurring_transaction_id', 'due_date'], $indexName);
                });
            }
        } else {
            Schema::table('transactions', function (Blueprint $table) use ($indexName) {
                $table->unique(['recurring_transaction_id', 'due_date'], $indexName);
            });
        }
    }

    public function down(): void
    {
        $indexName = 'transactions_recurring_due_date_unique';
        $driver = DB::getDriverName();

        if (in_array($driver, ['pgsql', 'sqlite'], true)) {
            DB::statement("DROP INDEX IF EXISTS {$indexName}");
        } else {
            Schema::table('transactions', function (Blueprint $table) use ($indexName) {
                $table->dropUnique($indexName);
            });
        }

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['recurring_transaction_id']);
            $table->dropColumn('recurring_transaction_id');
            $table->dropColumn('due_date');
        });
    }
};
