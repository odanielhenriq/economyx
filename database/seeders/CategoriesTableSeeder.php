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
            ['name' => 'Mercado'],
            ['name' => 'Transporte'],
            ['name' => 'Assinaturas'],
            ['name' => 'Lazer'],
            ['name' => 'Comida'],
            ['name' => 'Viagens'],
            ['name' => 'Educação'],
            ['name' => 'Saúde'],
            ['name' => 'Contas'],
            ['name' => 'Empréstimos'],
        ]);
    }
}
