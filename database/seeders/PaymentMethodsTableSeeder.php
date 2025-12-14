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
                'name' => 'Cartão de Crédito',
                'slug' => 'cc',
            ],
            [
                'name' => 'Cartão de Débito',
                'slug' => 'cd',
            ],
            [
                'name' => 'Pix',
                'slug' => 'px',
            ],
            [
                'name' => 'PayPal',
                'slug' => 'pp',
            ],
            [
                'name' => 'Transferência Bancária',
                'slug' => 'tb',
            ],
            [
                'name' => 'Dinheiro',
                'slug' => 'dn'
            ],
            [
                'name' => 'Cheque',
                'slug' => 'ch'
            ],
            [
                'name' => 'Boleto Bancário',
                'slug' => 'bb'
            ],
            [
                'name' => 'Vale Alimentação',
                'slug' => 'va'
            ],
            [
                'name' => 'Vale Refeição',
                'slug' => 'vr'
            ],

        ]);
    }
}
