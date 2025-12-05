<?php

namespace Database\Seeders;

use Illuminate\Container\Attributes\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB as FacadesDB;

class PaymentMethodsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        FacadesDB::table("payment_methods")->insert([
            [
                'name' => 'Credit Card',
                'closing_day' => 28,
                'due_day' => 30,
            ],
            [
                'name' => 'Debit Card',
                'closing_day' => null,
                'due_day' => null,
            ],
            [
                'name' => 'Pix',
                'closing_day' => null,
                'due_day' => null,
            ],
            [
                'name' => 'PayPal',
                'closing_day' => null,
                'due_day' => null,
            ],
            [
                'name' => 'Bank Transfer',
                'closing_day' => null,
                'due_day' => null,
            ],
            [
                'name' => 'Cash',
                'closing_day' => null,
                'due_day' => null,
            ],
        ]);
    }
}
