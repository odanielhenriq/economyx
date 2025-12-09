<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class UserRelationsTableSeeder extends Seeder
{
    public function run(): void
    {
        $daniel = User::where('email', 'daniel.henrique00@hotmail.com')->firstOrFail();
        $joyce  = User::where('email', 'joycebvb@gmail.com')->firstOrFail();

        DB::table('user_relations')->insert([
            [
                'user_id'        => $daniel->id,
                'related_user_id'=> $joyce->id,
                'relation_type'  => 'partner',
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
            [
                'user_id'        => $joyce->id,
                'related_user_id'=> $daniel->id,
                'relation_type'  => 'partner',
                'created_at'     => now(),
                'updated_at'     => now(),
            ],
        ]);
    }
}
