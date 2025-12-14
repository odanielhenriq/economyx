<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('categories')->insert([
            [
                'name' => 'Mercado',
                'slug' => 'mc'
            ],
            [
                'name' => 'Transporte',
                'slug' => 'tr'
            ],
            [
                'name' => 'Assinaturas',
                'slug' => 'ss'
            ],
            [
                'name' => 'Lazer',
                'slug' => 'lz'
            ],
            [
                'name' => 'Comida',
                'slug' => 'cm'
            ],
            [
                'name' => 'Viagens',
                'slug' => 'vg'
            ],
            [
                'name' => 'Educação',
                'slug' => 'ed'
            ],
            [
                'name' => 'Saúde',
                'slug' => 'sd'
            ],
            [
                'name' => 'Contas',
                'slug' => 'ct'
            ],
            [
                'name' => 'Empréstimos',
                'slug' => 'ep'
            ],
            [
                'name' => 'Casamento',
                'slug' => 'cm'
            ],
            [
                'name' => 'Ferramentas',
                'slug' => 'fm'
            ],
            [
                'name' => 'Salário',
                'slug' => 'sl'
            ],
            [
                'name' => 'Freelas',
                'slug' => 'fl'
            ],
            [
                'name' => 'Investimentos',
                'slug' => 'iv'
            ],
            [
                'name' => 'Vestuário',
                'slug' => 'vs'
            ],
            [
                'name' => 'Outros',
                'slug' => 'ot'
            ],
        ]);
    }
}
