<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeders de desenvolvimento local com dados pessoais.
 * Executado automaticamente pelo DatabaseSeeder quando APP_ENV=local.
 *
 * Categorias, tipos e formas de pagamento vêm da migration seed_reference_data.
 */
class LocalDevSeeder extends Seeder
{
    public function run(): void
    {
        $seeders = [
            UsersTableSeeder::class,
            CreditCardsTableSeeder::class,
            UserRelationsTableSeeder::class,
            CreditCardUserTableSeeder::class,
        ];

        if (class_exists(RealTransactionsSeeder::class)) {
            $seeders[] = RealTransactionsSeeder::class;
        }

        $this->call($seeders);
    }
}

