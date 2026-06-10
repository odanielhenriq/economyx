<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $users = [
            [
                'name' => 'Daniel',
                'email' => 'daniel.henrique00@hotmail.com',
                'password' => Hash::make('password'),
                'onboarding_completed_at' => $now,
                'onboarding_step' => 'done',
            ],
            [
                'name' => 'Joyce',
                'email' => 'joycebvb@gmail.com',
                'password' => Hash::make('password'),
                'onboarding_completed_at' => $now,
                'onboarding_step' => 'done',
            ],
        ];

        foreach ($users as $user) {
            if (DB::table('users')->where('email', $user['email'])->exists()) {
                continue;
            }

            DB::table('users')->insert([
                ...$user,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
