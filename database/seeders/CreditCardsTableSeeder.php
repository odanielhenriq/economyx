<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreditCardsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('credit_cards')->insert([
            [
                'name' => 'Santander',
                'closing_day' => 28,
                'due_day' => 30,
            ],
            [
                'name' => 'Nubank',
                'closing_day' => 28,
                'due_day' => 30,
            ],
             [
                'name' => 'Will Bank',
                'closing_day' => 28,
                'due_day' => 30,
            ],
        ]);
    }
}
