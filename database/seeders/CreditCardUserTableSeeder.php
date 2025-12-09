<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\CreditCard;

class CreditCardUserTableSeeder extends Seeder
{
    public function run(): void
    {
        $daniel = User::where('email', 'daniel.henrique00@hotmail.com')->firstOrFail();
        $joyce  = User::where('email', 'joycebvb@gmail.com')->firstOrFail();

        $santanderDaniel = CreditCard::where('name', 'Santander')
            ->where('owner_name', 'Daniel')
            ->firstOrFail();

        $santanderJoyce = CreditCard::where('name', 'Santander')
            ->where('owner_name', 'Joyce')
            ->firstOrFail();

        $nubankJoyce = CreditCard::where('name', 'Nubank')
            ->where('owner_name', 'Joyce')
            ->firstOrFail();

        $willJoyce = CreditCard::where('name', 'Will Bank')
            ->where('owner_name', 'Joyce')
            ->firstOrFail();

        $magaluJoyce = CreditCard::where('name', 'Magalu')
            ->where('owner_name', 'Joyce')
            ->firstOrFail();

        $magaluNeusa = CreditCard::where('name', 'Magalu')
            ->where('owner_name', 'Neusa')
            ->firstOrFail();

        DB::table('credit_card_user')->insert([
            // Santander (Daniel) – só Daniel usa
            [
                'credit_card_id' => $santanderDaniel->id,
                'user_id'        => $daniel->id,
                'created_at'     => now(),
                'updated_at'     => now(),
            ],

            // Santander (Joyce) – o casal usa
            [
                'credit_card_id' => $santanderJoyce->id,
                'user_id'        => $daniel->id,
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
            [
                'credit_card_id' => $santanderJoyce->id,
                'user_id'        => $joyce->id,
                'created_at'     => now(),
                'updated_at'     => now(),
            ],

            // Nubank (Joyce) – o casal usa
            [
                'credit_card_id' => $nubankJoyce->id,
                'user_id'        => $daniel->id,
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
            [
                'credit_card_id' => $nubankJoyce->id,
                'user_id'        => $joyce->id,
                'created_at'     => now(),
                'updated_at'     => now(),
            ],

            // Will Bank (Joyce) – só Joyce usa (exemplo)
            [
                'credit_card_id' => $willJoyce->id,
                'user_id'        => $joyce->id,
                'created_at'     => now(),
                'updated_at'     => now(),
            ],

            // Magalu (Joyce) – o casal usa
            [
                'credit_card_id' => $magaluJoyce->id,
                'user_id'        => $daniel->id,
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
            [
                'credit_card_id' => $magaluJoyce->id,
                'user_id'        => $joyce->id,
                'created_at'     => now(),
                'updated_at'     => now(),
            ],

            // Magalu Neusa – cartão emprestado pro casal
            [
                'credit_card_id' => $magaluNeusa->id,
                'user_id'        => $daniel->id,
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
            [
                'credit_card_id' => $magaluNeusa->id,
                'user_id'        => $joyce->id,
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
        ]);
    }
}
