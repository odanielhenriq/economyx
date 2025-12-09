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
                'alias'         => null,
                'closing_day'   => 28,
                'due_day'       => 30,
                'limit'         => null,
                'owner_user_id' => $daniel->id,
                'owner_name'    => 'Daniel',
                'is_shared'     => true,
            ],
            [
                'name'          => 'Santander',
                'alias'         => null,
                'closing_day'   => 28,
                'due_day'       => 30,
                'limit'         => null,
                'owner_user_id' => $joyce->id,
                'owner_name'    => 'Joyce',
                'is_shared'     => true,
            ],
            [
                'name'          => 'Nubank',
                'alias'         => null,
                'closing_day'   => 5,
                'due_day'       => 11,
                'limit'         => null,
                'owner_user_id' => $joyce->id,
                'owner_name'    => 'Joyce',
                'is_shared'     => true,
            ],
            [
                'name'          => 'Will Bank',
                'alias'         => null,
                'closing_day'   => 28,
                'due_day'       => 30,
                'limit'         => null,
                'owner_user_id' => $joyce->id,
                'owner_name'    => 'Joyce',
                'is_shared'     => true,
            ],
            [
                'name'          => 'Magalu',
                'alias'         => null,
                'closing_day'   => 28,
                'due_day'       => 30,
                'limit'         => null,
                'owner_user_id' => $joyce->id,
                'owner_name'    => 'Joyce',
                'is_shared'     => true,
            ],
            [
                'name'          => 'Magalu',
                'alias'         => null,
                'closing_day'   => 28,
                'due_day'       => 30,
                'limit'         => null,
                'owner_user_id' => null,
                'owner_name'    => 'Neusa',
                'is_shared'     => true, // pode deixar true pra vocês verem esse cartão
            ],
        ]);
    }
}
