<?php

namespace Database\Seeders;

use App\Models\User;
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
        $daniel = User::where('email', 'daniel.henrique00@hotmail.com')->firstOrFail();
        $joyce  = User::where('email', 'joycebvb@gmail.com')->firstOrFail();

        DB::table('credit_cards')->insert([
            [
                'name'          => 'Santander',
                'alias'         => 'Santander Daniel',
                'closing_day'   => 30,
                'due_day'       => 8,
                'limit'         => null,
                'owner_user_id' => $daniel->id,
                'owner_name'    => 'Daniel',
                'is_shared'     => true,
            ],
            [
                'name'          => 'Santander',
                'alias'         => 'Santander Joyce',
                'closing_day'   => 31,
                'due_day'       => 8,
                'limit'         => null,
                'owner_user_id' => $joyce->id,
                'owner_name'    => 'Joyce',
                'is_shared'     => true,
            ],
            [
                'name'          => 'Nubank',
                'alias'         => 'Nubank Joyce',
                'closing_day'   => 4,
                'due_day'       => 12,
                'limit'         => null,
                'owner_user_id' => $joyce->id,
                'owner_name'    => 'Joyce',
                'is_shared'     => true,
            ],
            [
                'name'          => 'Will Bank',
                'alias'         => 'Will Bank Joyce',
                'closing_day'   => 10,
                'due_day'       => 15,
                'limit'         => null,
                'owner_user_id' => $joyce->id,
                'owner_name'    => 'Joyce',
                'is_shared'     => true,
            ],
            [
                'name'          => 'Mercado Pago',
                'alias'         => 'Mercado Pago Daniel',
                'closing_day'   => 2,
                'due_day'       => 7,
                'limit'         => null,
                'owner_user_id' => $daniel->id,
                'owner_name'    => 'Daniel',
                'is_shared'     => true,
            ],
            [
                'name'          => 'Magalu',
                'alias'         => 'Magalu Joyce',
                'closing_day'   => 28,
                'due_day'       => 30,
                'limit'         => null,
                'owner_user_id' => $joyce->id,
                'owner_name'    => 'Joyce',
                'is_shared'     => true,
            ],
            [
                'name'          => 'Magalu',
                'alias'         => 'Magalu Neusa',
                'closing_day'   => 28,
                'due_day'       => 30,
                'limit'         => null,
                'owner_user_id' => null,
                'owner_name'    => 'Neusa',
                'is_shared'     => true,
            ],
        ]);
    }
}
