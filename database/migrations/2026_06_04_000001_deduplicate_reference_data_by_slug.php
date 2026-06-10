<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->deduplicateTable('categories', [
            'transactions' => 'category_id',
            'category_budgets' => 'category_id',
            'recurring_transactions' => 'category_id',
        ]);

        $this->deduplicateTable('types', [
            'transactions' => 'type_id',
            'recurring_transactions' => 'type_id',
        ]);

        $this->deduplicateTable('payment_methods', [
            'transactions' => 'payment_method_id',
            'recurring_transactions' => 'payment_method_id',
        ]);
    }

    public function down(): void
    {
        // Deduplicação não é reversível com segurança.
    }

    /**
     * @param  array<string, string>  $foreignKeys  tabela => coluna FK
     */
    private function deduplicateTable(string $table, array $foreignKeys): void
    {
        $duplicateSlugs = DB::table($table)
            ->select('slug')
            ->groupBy('slug')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('slug');

        foreach ($duplicateSlugs as $slug) {
            $ids = DB::table($table)
                ->where('slug', $slug)
                ->orderBy('id')
                ->pluck('id');

            $keepId = $ids->first();
            $duplicateIds = $ids->slice(1)->values()->all();

            if ($keepId === null || $duplicateIds === []) {
                continue;
            }

            foreach ($foreignKeys as $fkTable => $fkColumn) {
                DB::table($fkTable)
                    ->whereIn($fkColumn, $duplicateIds)
                    ->update([$fkColumn => $keepId]);
            }

            DB::table($table)->whereIn('id', $duplicateIds)->delete();
        }
    }
};
