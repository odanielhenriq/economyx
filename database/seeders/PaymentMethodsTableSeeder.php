<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SeedsReferenceRows;
use Illuminate\Database\Seeder;

class PaymentMethodsTableSeeder extends Seeder
{
    use SeedsReferenceRows;

    public function run(): void
    {
        $this->seedRowsBySlug('payment_methods', [
            ['name' => 'Cartão de Crédito', 'slug' => 'cc'],
            ['name' => 'Cartão de Débito', 'slug' => 'cd'],
            ['name' => 'Pix', 'slug' => 'px'],
            ['name' => 'PayPal', 'slug' => 'pp'],
            ['name' => 'Transferência Bancária', 'slug' => 'tb'],
            ['name' => 'Dinheiro', 'slug' => 'dn'],
            ['name' => 'Cheque', 'slug' => 'ch'],
            ['name' => 'Boleto Bancário', 'slug' => 'bb'],
            ['name' => 'Vale Alimentação', 'slug' => 'va'],
            ['name' => 'Vale Refeição', 'slug' => 'vr'],
        ]);
    }
}
