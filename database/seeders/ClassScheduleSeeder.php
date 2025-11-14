<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ClassScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $classrooms = DB::table('classrooms')->get();
        $users = DB::table('users')->pluck('id');

        $colors = ['#3B82F6', '#EF4444', '#10B981', '#F59E0B', '#8B5CF6', '#EC4899'];
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

        $schedules = [];

        foreach ($classrooms as $classroom) {
            // Create 2-3 schedules per classroom
            for ($i = 0; $i < rand(2, 3); $i++) {
                $day = $days[array_rand($days)];
                $startHour = rand(7, 15);

                $startTime = Carbon::parse("next $day")->setTime($startHour, 0);
                $endTime = $startTime->copy()->addHours(2);

                $schedules[] = [
                    'classroom_id' => $classroom->id,
                    'coordinator_id' => $users->random(),
                    'title' => $classroom->name,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'location' => 'Ruang ' . chr(65 + rand(0, 4)) . rand(101, 305),
                    'lecturer' => $this->getRandomLecturer(),
                    'description' => 'Kuliah pertemuan ' . ($i + 1),
                    'color' => $colors[array_rand($colors)],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        DB::table('class_schedules')->insert($schedules);
    }

    private function getRandomLecturer(): string
    {
        $lecturers = [
            'Dr. Ahmad Sutrisno, M.Kom',
            'Dr. Siti Nurhaliza, S.T., M.T.',
            'Budi Santoso, S.Kom., M.Cs',
            'Prof. Rina Wijaya, Ph.D',
            'Drs. Hendra Wijaya, M.Kom',
            'Dr. Eng. Fitri Rahmawati',
            'Ir. Joko Widodo, M.T.',
        ];

        return $lecturers[array_rand($lecturers)];
    }
}
