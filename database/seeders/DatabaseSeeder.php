<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Dados de referência (categorias, tipos, formas de pagamento) vêm da migration seed_reference_data.
     * Em ambiente local, também popula usuários e dados de desenvolvimento via LocalDevSeeder.
     */
    public function run(): void
    {
        if (app()->environment('local')) {
            $this->call(LocalDevSeeder::class);
        }
    }
}
