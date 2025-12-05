<?php

namespace App\Http\Controllers;

use App\Models\ClassSchedule;
use App\Models\Classroom;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as BaseController;

class ClassScheduleController extends BaseController
{
    use AuthorizesRequests;

    protected $notificationService;

    /**
     * Create a new controller instance.
     */
    public function __construct(NotificationService $notificationService)
    {
        $this->middleware('auth:api');
        $this->notificationService = $notificationService;
    }

    /**
     * Display a listing of class schedules for a specific classroom.
     *
     * @param  int  $classroomId
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request, $classroomId)
    {
        $classroom = Classroom::findOrFail($classroomId);

        // Check authorization - user must be member of classroom
        $this->authorize('view', [ClassSchedule::class, $classroom]);

        $query = ClassSchedule::where('classroom_id', $classroomId)
            ->with(['coordinator1:id,name,email', 'coordinator2:id,name,email', 'reminders'])
            ->orderBy('start_time', 'asc');

        if ($request->has('start_date')) {
            $query->whereDate('start_time', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->whereDate('end_time', '<=', $request->end_date);
        }

        $schedules = $query->get();

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
            'repeat_days' => 'nullable|array',
            'repeat_days.*' => 'integer|min:1|max:7',
            'repeat_count' => 'nullable|integer|min:1|max:52',
            'reminders' => 'nullable|array',
            'reminders.*' => 'integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if repeat is enabled
        $repeatDays = $request->repeat_days ?? [];
        $repeatCount = $request->repeat_count ?? 1;
        $hasRepeat = !empty($repeatDays) && $repeatCount > 1;

        if ($hasRepeat) {
            // Create multiple schedules based on repeat pattern
            $createdSchedules = $this->createRepeatingSchedules(
                $classroom,
                $request,
                $repeatDays,
                $repeatCount
            );

            return response()->json([
                'message' => count($createdSchedules) . ' jadwal kelas berhasil dibuat',
                'data' => $createdSchedules
            ], 201);
        }

        // Create single schedule
        $schedule = \Illuminate\Support\Facades\DB::transaction(function () use ($request, $classroomId, $classroom) {
            $schedule = ClassSchedule::create([
                'classroom_id' => $classroomId,
                'coordinator_1' => $request->coordinator_1 ?? $classroom->owner_id,
                'coordinator_2' => $request->coordinator_2 ?? $classroom->owner_id,
                'title' => $request->title,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'location' => $request->location,
                'lecturer' => $request->lecturer,
                'description' => $request->description,
                'color' => $request->color ?? '#5CD9C1',
            ]);

            // Create reminders if requested
            $reminders = $request->input('reminders');
            if (is_array($reminders)) {
                foreach ($reminders as $minutes) {
                    if ($minutes > 0) {
                        \App\Models\Reminder::create([
                            'remindable_id' => $schedule->id,
                            'remindable_type' => ClassSchedule::class,
                            'minutes_before_start' => $minutes,
                            'status' => 'pending',
                        ]);
                    }
                }
            }

            return $schedule;
        });

        // Load relationships
        $schedule->load(['coordinator1:id,name,email', 'coordinator2:id,name,email', 'reminders']);

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

        // Prepare update data
        $updateData = $request->all();

        // If coordinator_1 is null, set to owner_id
        if (!isset($updateData['coordinator_1']) || $updateData['coordinator_1'] === null) {
            $updateData['coordinator_1'] = $classroom->owner_id;
        }

        // If coordinator_2 is null, set to owner_id
        if (!isset($updateData['coordinator_2']) || $updateData['coordinator_2'] === null) {
            $updateData['coordinator_2'] = $classroom->owner_id;
        }

        $schedule->update($updateData);

        // Update reminders if provided
        if ($request->has('reminders')) {
            // Delete existing reminders
            $schedule->reminders()->delete();

            // Create new reminders
            $reminders = $request->input('reminders');
            if (is_array($reminders)) {
                foreach ($reminders as $minutes) {
                    if ($minutes > 0) {
                        \App\Models\Reminder::create([
                            'remindable_id' => $schedule->id,
                            'remindable_type' => ClassSchedule::class,
                            'minutes_before_start' => $minutes,
                            'status' => 'pending',
                        ]);
                    }
                }
            }
        }

        // Load relationships
        $schedule->load(['coordinator1:id,name,email', 'coordinator2:id,name,email', 'reminders']);

        // Send notification to all classroom members
        $this->notifyClassroomMembers($classroom, $schedule);

        return response()->json([
            'message' => 'Jadwal kelas berhasil diperbarui',
            'data' => $schedule
        ], 200);
    }

    private function notifyClassroomMembers(Classroom $classroom, ClassSchedule $schedule)
    {
        // Get all members and owner
        $users = $classroom->users;
        if (!$users->contains('id', $classroom->owner_id)) {
            $users->push($classroom->owner);
        }

        // Filter out current user
        $currentUser = Auth::user();
        $usersToNotify = $users->filter(function ($user) use ($currentUser) {
            return $user->id !== $currentUser->id;
        });

        if ($usersToNotify->isEmpty()) {
            return;
        }

        $title = "Jadwal Diperbarui: {$schedule->title}";
        $date = Carbon::parse($schedule->start_time)->format('d M Y');
        $time = Carbon::parse($schedule->start_time)->format('H:i');
        $body = "Jadwal untuk {$date} pukul {$time} telah diperbarui.";

        $data = [
            'schedule_id' => (string) $schedule->id,
            'type' => 'class_schedule_update',
            'classroom_id' => (string) $classroom->id,
        ];

        $this->notificationService->sendToUsers($usersToNotify, $title, $body, $data);
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

    /**
     * Create multiple schedules based on repeat pattern.
     *
     * @param  \App\Models\Classroom  $classroom
     * @param  \Illuminate\Http\Request  $request
     * @param  array  $repeatDays  Array of day numbers (1=Monday, 7=Sunday)
     * @param  int  $repeatCount  Number of times to repeat
     * @return array
     */
    private function createRepeatingSchedules($classroom, $request, $repeatDays, $repeatCount)
    {
        $startTime = Carbon::parse($request->start_time);
        $endTime = Carbon::parse($request->end_time);
        return \Illuminate\Support\Facades\DB::transaction(function () use ($classroom, $request, $repeatDays, $repeatCount, $startTime, $endTime) {
            $schedulesToInsert = [];
            $currentDate = $startTime->copy();

            // Loop through repeat count
            for ($week = 0; $week < $repeatCount; $week++) {
                // For each selected day in the week
                foreach ($repeatDays as $dayOfWeek) {
                    // Calculate the date for this day in this week
                    $scheduleDate = $currentDate->copy()->startOfWeek()->addDays($dayOfWeek - 1);

                    // Skip if the date is in the past
                    if ($scheduleDate->isPast() && !$scheduleDate->isToday()) {
                        continue;
                    }

                    // Create start and end datetime for this schedule
                    $scheduleStart = $scheduleDate->copy()
                        ->setTime($startTime->hour, $startTime->minute, $startTime->second);
                    $scheduleEnd = $scheduleDate->copy()
                        ->setTime($endTime->hour, $endTime->minute, $endTime->second);

                    $schedule = ClassSchedule::create([
                        'classroom_id' => $classroom->id,
                        'coordinator_1' => $request->coordinator_1 ?? $classroom->owner_id,
                        'coordinator_2' => $request->coordinator_2 ?? $classroom->owner_id,
                        'title' => $request->title,
                        'start_time' => $scheduleStart->toDateTimeString(),
                        'end_time' => $scheduleEnd->toDateTimeString(),
                        'location' => $request->location,
                        'lecturer' => $request->lecturer,
                        'description' => $request->description,
                        'color' => $request->color ?? '#5CD9C1',
                    ]);

                    // Create reminders if requested
                    $reminders = $request->input('reminders');
                    if (is_array($reminders)) {
                        foreach ($reminders as $minutes) {
                            if ($minutes > 0) {
                                \App\Models\Reminder::create([
                                    'remindable_id' => $schedule->id,
                                    'remindable_type' => ClassSchedule::class,
                                    'minutes_before_start' => $minutes,
                                    'status' => 'pending',
                                ]);
                            }
                        }
                    }

                    $schedule->load('reminders');
                    $schedulesToInsert[] = $schedule;
                }

                // Move to next week
                $currentDate->addWeek();
            }

            return $schedulesToInsert;
        });
    }
}
