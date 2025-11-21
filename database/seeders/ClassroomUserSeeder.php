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
        $classrooms = DB::table('classrooms')->get();

        $classroomUsers = [];

        foreach ($classrooms as $classroom) {
            // Get existing members (owner already added in ClassroomSeeder)
            $existingMembers = DB::table('classroom_user')
                ->where('classroom_id', $classroom->id)
                ->pluck('user_id')
                ->toArray();

            // Get available users (not owner, not already member)
            $availableUsers = $users->filter(function ($userId) use ($existingMembers) {
                return !in_array($userId, $existingMembers);
            });

            // Add 2-6 more random users to each classroom (owner already added, so total will be 3-7)
            if ($availableUsers->isNotEmpty()) {
                $numberOfUsers = min(rand(2, 6), $availableUsers->count());
                $randomUsers = $availableUsers->random(min($numberOfUsers, $availableUsers->count()));

                foreach ($randomUsers as $userId) {
                    $classroomUsers[] = [
                        'classroom_id' => $classroom->id,
                        'user_id' => $userId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        if (!empty($classroomUsers)) {
            DB::table('classroom_user')->insert($classroomUsers);
        }
    }
}
