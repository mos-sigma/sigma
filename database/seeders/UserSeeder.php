<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
            'id' => 1,
            'email' => 'nico@sigmie.com',
            'email_verified_at' => null,
            'password' => '$2y$10$3HE6WsVokRAUvioLJfQ5CedIjp9Xz1ylcG2VqWiH.h1Q9MtUNa3lq', //demo
            'remember_token' => null,
            'github' => '0',
            'avatar_url' => 'https://avatars2.githubusercontent.com/u/15706832?v=4',
            'username' => 'nico'
        ]);

        DB::table('subscriptions')->insert([
            'id' => 1,
            'billable_id' => 1,
            'billable_type' => 'user',
            'name' => 'hobby',
            'paddle_id' => '83637',
            'paddle_status' => 'trialing',
            'paddle_plan' => '7669',
            'quantity' => 1,
            'trial_ends_at' => '2021-03-04 00:00:00',
            'paused_from' => null,
            'ends_at' => null,
        ]);
    }
}
