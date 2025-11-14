<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClassroomUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = DB::table('users')->pluck('id');
        $classrooms = DB::table('classrooms')->pluck('id');

        $classroomUsers = [];

        foreach ($classrooms as $classroomId) {
            // Add 3-7 random users to each classroom
            $randomUsers = $users->random(rand(3, 7));

            foreach ($randomUsers as $userId) {
                $classroomUsers[] = [
                    'classroom_id' => $classroomId,
                    'user_id' => $userId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        DB::table('classroom_user')->insert($classroomUsers);
    }
}
