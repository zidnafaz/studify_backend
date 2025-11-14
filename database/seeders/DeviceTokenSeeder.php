<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DeviceTokenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = DB::table('users')->pluck('id');
        $platforms = ['android', 'ios'];

        $tokens = [];

        foreach ($users as $userId) {
            // Each user can have 1-2 devices
            for ($i = 0; $i < rand(1, 2); $i++) {
                $tokens[] = [
                    'user_id' => $userId,
                    'token' => 'fcm_' . Str::random(152),
                    'platform' => $platforms[array_rand($platforms)],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        DB::table('device_tokens')->insert($tokens);
    }
}
