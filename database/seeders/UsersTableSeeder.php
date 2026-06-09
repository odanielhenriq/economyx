<?php

namespace Database\Seeders;


use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        DB::table('users')->insert([
            [
                'name' => 'Daniel',
                'email' => 'daniel@example.com',
                'password' => Hash::make('password'),
                'onboarding_completed_at' => $now,
                'onboarding_step' => 'done',
            ],
            [
                'name' => 'Joyce',
                'email' => 'joyce@example.com',
                'password' => Hash::make('password'),
                'onboarding_completed_at' => $now,
                'onboarding_step' => 'done',
            ],
        ]);
    }
}
