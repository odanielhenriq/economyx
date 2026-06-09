<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Seeders de desenvolvimento local com dados pessoais.
 * Não executado automaticamente — use: php artisan db:seed --class=LocalDevSeeder
 */
class LocalDevSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UsersTableSeeder::class,
            CategoriesTableSeeder::class,
            TypesTableSeeder::class,
            PaymentMethodsTableSeeder::class,
            CreditCardsTableSeeder::class,
            UserRelationsTableSeeder::class,
            CreditCardUserTableSeeder::class,
        ]);
    }
}
