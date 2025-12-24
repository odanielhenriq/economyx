<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        $this->call([
            UsersTableSeeder::class,
            CategoriesTableSeeder::class,
            TypesTableSeeder::class,
            PaymentMethodsTableSeeder::class,
            CreditCardsTableSeeder::class,
            UserRelationsTableSeeder::class,
            CreditCardUserTableSeeder::class,
            // faker
            // TransactionsTableSeeder::class,

            // seus dados reais:
            RealTransactionsSeeder::class,
        ]);
    }
}
