<?php

namespace Database\Seeders;

use App\Models\Transaction;
use App\Models\User;
use App\Models\CreditCard;

use Illuminate\Database\Seeder;

class TransactionsTableSeeder extends Seeder
{
    public function run(): void
    {
        $daniel = User::where('email', 'daniel.henrique00@hotmail.com')->first();
        $joyce  = User::where('email', 'joycebvb@gmail.com')->first();

        $users = [$daniel->id, $joyce->id];

        $creditCardIds = CreditCard::pluck('id')->all();
        // ID do método "Credit Card" (de acordo com sua seed atual)
        $CREDIT_CARD_METHOD_ID = 1;

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

            // 1) Sorteia o método de pagamento
            $paymentMethodId = fake()->numberBetween(1, 3); // 1 = crédito, 2 = débito, 3 = pix (pela sua seed)

            // 2) Se for crédito, sorteia um cartão. Senão, deixa null.
            $creditCardId = null;

            if ($paymentMethodId === $CREDIT_CARD_METHOD_ID && !empty($creditCardIds)) {
                $creditCardId = fake()->randomElement($creditCardIds);
            }

            $transaction = Transaction::create([
                'description'       => fake()->sentence(2),
                'total_amount'            => fake()->randomFloat(2, 10, 500),
                'amount'            => fake()->randomFloat(2, 10, 500),
                'transaction_date'  => fake()->dateTimeBetween('-1 year', 'now'),
                'category_id'       => fake()->numberBetween(1, 5),
                'type_id'           => fake()->numberBetween(1, 2),
                'payment_method_id' => $paymentMethodId,
                'credit_card_id'    => $creditCardId, // 👈 aqui

                'installment_number' => $installment_number,
                'installment_total'  => $installment_total,
            ]);

            // Distribuição de usuários
            $assignedUsers = fake()->randomElements($users, fake()->numberBetween(1, 2));
            $transaction->users()->attach($assignedUsers);
        }
    }
}
