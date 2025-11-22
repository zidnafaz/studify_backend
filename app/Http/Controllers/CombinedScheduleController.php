<?php

namespace App\Http\Controllers;

use App\Models\PersonalSchedule;
use App\Models\ClassSchedule;
use App\Models\Classroom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CombinedScheduleController extends Controller
{
    /**
     * MENAMPILKAN SEMUA JADWAL GABUNGAN (GET)
     * Menggabungkan personal schedules dan class schedules dari semua classroom user
     * 
     * Query Parameters:
     * - source: Filter berdasarkan sumber data
     *   - 'personal' : Hanya personal schedules
     *   - 'classroom:{id}' : Hanya schedules dari classroom tertentu (contoh: 'classroom:1')
     *   - null/tidak ada : Semua schedules (personal + semua classrooms)
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $source = $request->query('source');

        $schedules = collect();

        // Jika source adalah 'personal' atau tidak ada filter, ambil personal schedules
        if (!$source || $source === 'personal') {
            $personalSchedules = PersonalSchedule::where('user_id', $user->id)
                ->orderBy('start_time', 'asc')
                ->get()
                ->map(function ($schedule) {
                    return [
                        'id' => $schedule->id,
                        'type' => 'personal',
                        'title' => $schedule->title,
                        'start_time' => $schedule->start_time,
                        'end_time' => $schedule->end_time,
                        'location' => $schedule->location,
                        'description' => $schedule->description,
                        'color' => $schedule->color,
                        'source_id' => null,
                        'source_name' => 'Personal Schedule',
                    ];
                });
            
            $schedules = $schedules->merge($personalSchedules);
        }

        // Jika source adalah 'classroom:{id}' atau tidak ada filter, ambil class schedules
        if (!$source || strpos($source, 'classroom:') === 0) {
            // Get all classrooms where user is owner or member
            $classrooms = Classroom::where('owner_id', $user->id)
                ->orWhereHas('users', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->get();

            // If specific classroom is requested, filter
            if ($source && strpos($source, 'classroom:') === 0) {
                $classroomId = (int) str_replace('classroom:', '', $source);
                $classrooms = $classrooms->where('id', $classroomId);
                
                // Check if user has access to this classroom
                if ($classrooms->isEmpty()) {
                    return response()->json([
                        'message' => 'Classroom not found or you do not have access',
                    ], 404);
                }
            }

            // Get all class schedules from accessible classrooms
            foreach ($classrooms as $classroom) {
                $classSchedules = ClassSchedule::where('classroom_id', $classroom->id)
                    ->with(['coordinator1:id,name,email', 'coordinator2:id,name,email'])
                    ->orderBy('start_time', 'asc')
                    ->get()
                    ->map(function ($schedule) use ($classroom) {
                        return [
                            'id' => $schedule->id,
                            'type' => 'class',
                            'title' => $schedule->title,
                            'start_time' => $schedule->start_time,
                            'end_time' => $schedule->end_time,
                            'location' => $schedule->location,
                            'lecturer' => $schedule->lecturer,
                            'description' => $schedule->description,
                            'color' => $schedule->color,
                            'coordinator_1' => $schedule->coordinator_1,
                            'coordinator_2' => $schedule->coordinator_2,
                            'coordinator1' => $schedule->coordinator1,
                            'coordinator2' => $schedule->coordinator2,
                            'source_id' => $classroom->id,
                            'source_name' => $classroom->name,
                        ];
                    });
                
                $schedules = $schedules->merge($classSchedules);
            }
        }

        // Sort all schedules by start_time
        $schedules = $schedules->sortBy('start_time')->values();

        // Get list of available sources for dropdown
        $availableSources = $this->getAvailableSources($user);

        return response()->json([
            'data' => $schedules,
            'meta' => [
                'total' => $schedules->count(),
                'available_sources' => $availableSources,
                'current_filter' => $source,
            ]
        ]);
    }

    /**
     * Get list of available sources for filtering
     * 
     * @param \App\Models\User $user
     * @return array
     */
    private function getAvailableSources($user)
    {
        $sources = [
            [
                'id' => 'all',
                'type' => 'all',
                'name' => 'All Schedules',
                'description' => 'Semua jadwal (personal + semua classroom)',
            ],
            [
                'id' => 'personal',
                'type' => 'personal',
                'name' => 'Personal Schedule',
                'description' => 'Jadwal pribadi',
            ],
        ];

        // Get all classrooms where user is owner or member
        $classrooms = Classroom::where('owner_id', $user->id)
            ->orWhereHas('users', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->select('id', 'name')
            ->get();

        // Add each classroom as a source option
        foreach ($classrooms as $classroom) {
            $sources[] = [
                'id' => 'classroom:' . $classroom->id,
                'type' => 'classroom',
                'name' => $classroom->name,
                'description' => 'Jadwal dari classroom: ' . $classroom->name,
                'classroom_id' => $classroom->id,
            ];
        }

        return $sources;
    }
}

