<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PersonalScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = DB::table('users')->pluck('id');
        $colors = ['#10B981', '#F59E0B', '#EC4899', '#8B5CF6', '#06B6D4'];

        $activities = [
            'Belajar Kelompok',
            'Mengerjakan Tugas',
            'Rapat Organisasi',
            'Gym',
            'Meeting Project',
            'Konsultasi Dosen',
            'Workshop',
            'Seminar',
        ];

        $locations = [
            'Perpustakaan',
            'Kantin',
            'Lab Komputer',
            'Ruang Diskusi',
            'Aula',
            'Online - Zoom',
            'Cafe',
            'Rumah',
        ];

        $schedules = [];

        foreach ($users as $userId) {
            // Create 2-5 personal schedules per user
            for ($i = 0; $i < rand(2, 5); $i++) {
                $startTime = Carbon::now()->addDays(rand(0, 14))->setTime(rand(8, 18), 0);
                $endTime = $startTime->copy()->addHours(rand(1, 3));

                $schedules[] = [
                    'user_id' => $userId,
                    'title' => $activities[array_rand($activities)],
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'location' => $locations[array_rand($locations)],
                    'description' => 'Jadwal pribadi untuk ' . strtolower($activities[array_rand($activities)]),
                    'color' => $colors[array_rand($colors)],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        DB::table('personal_schedules')->insert($schedules);
    }
}
