<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReminderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $classSchedules = DB::table('class_schedules')->pluck('id');
        $personalSchedules = DB::table('personal_schedules')->pluck('id');

        $reminders = [];
        $minutesBefore = [15, 30, 60, 120];
        $statuses = ['pending', 'sent'];

        // Create reminders for class schedules
        foreach ($classSchedules as $scheduleId) {
            if (rand(0, 1)) { // 50% chance to have reminder
                $reminders[] = [
                    'remindable_id' => $scheduleId,
                    'remindable_type' => 'App\\Models\\ClassSchedule',
                    'minutes_before_start' => $minutesBefore[array_rand($minutesBefore)],
                    'status' => $statuses[array_rand($statuses)],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        // Create reminders for personal schedules
        foreach ($personalSchedules as $scheduleId) {
            if (rand(0, 1)) { // 50% chance to have reminder
                $reminders[] = [
                    'remindable_id' => $scheduleId,
                    'remindable_type' => 'App\\Models\\PersonalSchedule',
                    'minutes_before_start' => $minutesBefore[array_rand($minutesBefore)],
                    'status' => $statuses[array_rand($statuses)],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        DB::table('reminders')->insert($reminders);
    }
}
