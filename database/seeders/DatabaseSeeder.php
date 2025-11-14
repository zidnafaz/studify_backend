<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create 20 users
        User::factory(20)->create();

        // Call other seeders
        $this->call([
            ClassroomSeeder::class,
            ClassroomUserSeeder::class,
            ClassScheduleSeeder::class,
            PersonalScheduleSeeder::class,
            DeviceTokenSeeder::class,
            ReminderSeeder::class,
        ]);
    }
}
