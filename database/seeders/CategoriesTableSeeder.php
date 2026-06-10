<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsReferenceRows;
use Illuminate\Database\Seeder;

class CategoriesTableSeeder extends Seeder
{
    use SeedsReferenceRows;

    public function run(): void
    {
        $this->seedRowsBySlug('categories', [
            ['name' => 'Mercado', 'slug' => 'mc'],
            ['name' => 'Transporte', 'slug' => 'tr'],
            ['name' => 'Assinaturas', 'slug' => 'ss'],
            ['name' => 'Lazer', 'slug' => 'lz'],
            ['name' => 'Comida', 'slug' => 'cm'],
            ['name' => 'Viagens', 'slug' => 'vg'],
            ['name' => 'Educação', 'slug' => 'ed'],
            ['name' => 'Saúde', 'slug' => 'sd'],
            ['name' => 'Contas', 'slug' => 'ct'],
            ['name' => 'Empréstimos', 'slug' => 'ep'],
            ['name' => 'Casamento', 'slug' => 'cs'],
            ['name' => 'Ferramentas', 'slug' => 'fm'],
            ['name' => 'Salário', 'slug' => 'sl'],
            ['name' => 'Freelas', 'slug' => 'fl'],
            ['name' => 'Investimentos', 'slug' => 'iv'],
            ['name' => 'Vestuário', 'slug' => 'vs'],
            ['name' => 'Outros', 'slug' => 'ot'],
        ]);
    }
}
