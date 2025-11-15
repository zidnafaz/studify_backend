<?php

namespace App\Http\Controllers;

use App\Models\ClassSchedule;
use App\Models\Classroom;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as BaseController;

class ClassScheduleController extends BaseController
{
    use AuthorizesRequests;

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     * Display a listing of class schedules for a specific classroom.
     *
     * @param  int  $classroomId
     * @return \Illuminate\Http\JsonResponse
     */
    public function index($classroomId)
    {
        $classroom = Classroom::findOrFail($classroomId);

        // Check authorization - user must be member of classroom
        $this->authorize('view', [ClassSchedule::class, $classroom]);

        $schedules = ClassSchedule::where('classroom_id', $classroomId)
            ->with(['coordinator1:id,name,email', 'coordinator2:id,name,email'])
            ->orderBy('start_time', 'asc')
            ->get();

        return response()->json([
            'data' => $schedules
        ], 200);
    }

    /**
     * Store a newly created class schedule.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $classroomId
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, $classroomId)
    {
        $classroom = Classroom::findOrFail($classroomId);

        // Check authorization - only classroom owner can create
        $this->authorize('create', [ClassSchedule::class, $classroom]);

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'location' => 'nullable|string|max:255',
            'lecturer' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'coordinator_1' => 'nullable|exists:users,id',
            'coordinator_2' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $schedule = ClassSchedule::create([
            'classroom_id' => $classroomId,
            'coordinator_1' => $request->coordinator_1,
            'coordinator_2' => $request->coordinator_2,
            'title' => $request->title,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'location' => $request->location,
            'lecturer' => $request->lecturer,
            'description' => $request->description,
            'color' => $request->color ?? '#5CD9C1',
        ]);

        // Load relationships
        $schedule->load(['coordinator1:id,name,email', 'coordinator2:id,name,email']);

        return response()->json([
            'message' => 'Jadwal kelas berhasil dibuat',
            'data' => $schedule
        ], 201);
    }

    /**
     * Display the specified class schedule.
     *
     * @param  int  $classroomId
     * @param  int  $scheduleId
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($classroomId, $scheduleId)
    {
        $classroom = Classroom::findOrFail($classroomId);
        $schedule = ClassSchedule::where('classroom_id', $classroomId)
            ->with(['coordinator1:id,name,email', 'coordinator2:id,name,email', 'classroom:id,name'])
            ->findOrFail($scheduleId);

        // Check authorization
        $this->authorize('view', [ClassSchedule::class, $classroom]);

        return response()->json([
            'data' => $schedule
        ], 200);
    }

    /**
     * Update the specified class schedule.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $classroomId
     * @param  int  $scheduleId
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $classroomId, $scheduleId)
    {
        $classroom = Classroom::findOrFail($classroomId);
        $schedule = ClassSchedule::where('classroom_id', $classroomId)
            ->findOrFail($scheduleId);

        // Check authorization - owner or coordinator can update
        $this->authorize('update', [$schedule, $classroom]);

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'start_time' => 'sometimes|required|date',
            'end_time' => 'sometimes|required|date|after:start_time',
            'location' => 'nullable|string|max:255',
            'lecturer' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'coordinator_1' => 'nullable|exists:users,id',
            'coordinator_2' => 'nullable|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $schedule->update($request->all());

        // Load relationships
        $schedule->load(['coordinator1:id,name,email', 'coordinator2:id,name,email']);

        return response()->json([
            'message' => 'Jadwal kelas berhasil diperbarui',
            'data' => $schedule
        ], 200);
    }

    /**
     * Remove the specified class schedule.
     *
     * @param  int  $classroomId
     * @param  int  $scheduleId
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($classroomId, $scheduleId)
    {
        $classroom = Classroom::findOrFail($classroomId);
        $schedule = ClassSchedule::where('classroom_id', $classroomId)
            ->findOrFail($scheduleId);

        // Check authorization - owner or coordinator can delete
        $this->authorize('delete', [$schedule, $classroom]);

        $schedule->delete();

        return response()->json([
            'message' => 'Jadwal kelas berhasil dihapus'
        ], 200);
    }
}
