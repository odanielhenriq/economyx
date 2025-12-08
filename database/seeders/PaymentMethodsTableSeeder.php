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
            ],
            [
                'name' => 'Debit Card',
            ],
            [
                'name' => 'Pix',
            ],
            [
                'name' => 'PayPal',
            ],
            [
                'name' => 'Bank Transfer',
            ],
            [
                'name' => 'Cash',
            ],
        ]);
    }
}
