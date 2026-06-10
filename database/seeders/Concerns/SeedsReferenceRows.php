<?php

namespace Database\Seeders\Concerns;

use Illuminate\Support\Facades\DB;

trait SeedsReferenceRows
{
    /**
     * Insere linhas de referência apenas quando o slug ainda não existe.
     *
     * @param  list<array<string, mixed>>  $rows
     */
    protected function seedRowsBySlug(string $table, array $rows): void
    {
        $now = now();

        foreach ($rows as $row) {
            if (DB::table($table)->where('slug', $row['slug'])->exists()) {
                continue;
            }

            DB::table($table)->insert([
                ...$row,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
