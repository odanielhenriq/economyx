<?php

namespace Database\Seeders;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;

class TransactionsTableSeeder extends Seeder
{
    public function run(): void
    {
        $daniel = User::where('email', 'daniel.henrique00@hotmail.com')->first();
        $joyce  = User::where('email', 'joycebvb@gmail.com')->first();

        $users = [$daniel->id, $joyce->id];

        for ($i = 0; $i < 50; $i++) {

            // 30% de chance de ter parcelamento
            $hasInstallments = fake()->boolean(30);

            if ($hasInstallments) {
                $installment_total  = fake()->numberBetween(2, 12);
                $installment_number = fake()->numberBetween(1, $installment_total - 1);
            } else {
                $installment_total  = null;
                $installment_number = null;
            }

            $transaction = Transaction::create([
                'description'       => fake()->sentence(2),
                'amount'            => fake()->randomFloat(2, 10, 500),
                'transaction_date'  => fake()->dateTimeBetween('-1 year', 'now'),
                'category_id'       => fake()->numberBetween(1, 5),
                'type_id'           => fake()->numberBetween(1, 2),
                'payment_method_id' => fake()->numberBetween(1, 3),

                'installment_number' => $installment_number,
                'installment_total'  => $installment_total,
            ]);

            // Distribuição de usuários
            $assignedUsers = fake()->randomElements($users, fake()->numberBetween(1, 2));
            $transaction->users()->attach($assignedUsers);
        }
    }
}
