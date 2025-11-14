<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ClassroomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = DB::table('users')->pluck('id');

        $classrooms = [
            [
                'owner_id' => $users->random(),
                'name' => 'Pemrograman Web',
                'unique_code' => strtoupper(Str::random(8)),
                'description' => 'Kelas untuk belajar pemrograman web menggunakan Laravel dan React',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'owner_id' => $users->random(),
                'name' => 'Basis Data',
                'unique_code' => strtoupper(Str::random(8)),
                'description' => 'Kelas basis data relasional dan non-relasional',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'owner_id' => $users->random(),
                'name' => 'Algoritma dan Struktur Data',
                'unique_code' => strtoupper(Str::random(8)),
                'description' => 'Mempelajari algoritma sorting, searching, dan struktur data',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'owner_id' => $users->random(),
                'name' => 'Matematika Diskrit',
                'unique_code' => strtoupper(Str::random(8)),
                'description' => 'Logika, graf, dan teori bilangan',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'owner_id' => $users->random(),
                'name' => 'Pemrograman Mobile',
                'unique_code' => strtoupper(Str::random(8)),
                'description' => 'Pengembangan aplikasi mobile Android dan iOS',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('classrooms')->insert($classrooms);
    }
}
