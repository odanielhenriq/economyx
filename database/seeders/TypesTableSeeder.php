<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsReferenceRows;
use Illuminate\Database\Seeder;

class TypesTableSeeder extends Seeder
{
    use SeedsReferenceRows;

    public function run(): void
    {
        $this->seedRowsBySlug('types', [
            ['name' => 'Receita', 'slug' => 'rc'],
            ['name' => 'Despesa', 'slug' => 'dc'],
        ]);
    }
}
