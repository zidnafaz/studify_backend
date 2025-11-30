<?php

namespace App\Http\Controllers;

use App\Models\PersonalSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;

class PersonalScheduleController extends Controller
{
    /**
     * MENAMPILKAN SEMUA JADWAL PRIBADI (GET)
     * Sesuai F-04 & F-05
     */
    public function index()
    {
        // Kita cuma ambil jadwal milik user yang sedang LOGIN
        // Jadi user A tidak bisa lihat jadwal user B.
        $schedules = PersonalSchedule::where('user_id', Auth::id())
            ->with('reminders')
            ->orderBy('start_time', 'asc')
            ->get();

        return response()->json([
            'data' => $schedules
        ]);
    }

    /**
     * MEMBUAT JADWAL BARU (POST)
     * Sesuai F-05
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after:start_time',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'reminders' => 'nullable|array',
            'reminders.*' => 'integer|min:1',
            'repeat_days' => 'nullable|array',
            'repeat_days.*' => 'integer|min:1|max:7',
            'repeat_count' => 'nullable|integer|min:1|max:52',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $data['user_id'] = Auth::id();

        \Illuminate\Support\Facades\Log::info('Creating Personal Schedule', ['data' => $data]);

        // Check if repeat is enabled
        $repeatDays = $request->repeat_days ?? [];
        $repeatCount = $request->repeat_count ?? 1;
        $hasRepeat = !empty($repeatDays) && $repeatCount > 1;

        if ($hasRepeat) {
            $createdSchedules = $this->createRepeatingSchedules($data, $repeatDays, $repeatCount);

            return response()->json([
                'message' => count($createdSchedules) . ' jadwal pribadi berhasil dibuat',
                'data' => $createdSchedules[0] ?? null, // Return first schedule or null
            ], 201);
        }

        // Create single schedule
        $schedule = \Illuminate\Support\Facades\DB::transaction(function () use ($data) {
            // Remove auxiliary data before creation
            $scheduleData = $data;
            unset($scheduleData['reminders']);
            unset($scheduleData['repeat_days']);
            unset($scheduleData['repeat_count']);

            $schedule = PersonalSchedule::create($scheduleData);

            // Create reminders
            if (isset($data['reminders']) && is_array($data['reminders'])) {
                foreach ($data['reminders'] as $minutes) {
                    if ($minutes > 0) {
                        \App\Models\Reminder::create([
                            'remindable_id' => $schedule->id,
                            'remindable_type' => PersonalSchedule::class,
                            'minutes_before_start' => $minutes,
                            'status' => 'pending',
                        ]);
                    }
                }
            }

            return $schedule;
        });

        $schedule->load('reminders');

        return response()->json([
            'message' => 'Jadwal pribadi berhasil dibuat',
            'data' => $schedule,
        ], 201);
    }

    /**
     * MELIHAT DETAIL 1 JADWAL (GET)
     */
    public function show($id)
    {
        $schedule = PersonalSchedule::where('user_id', Auth::id())
            ->with('reminders')
            ->find($id);

        if (!$schedule) {
            return response()->json([
                'message' => 'Jadwal pribadi tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'data' => $schedule,
        ]);
    }

    /**
     * MENGEDIT JADWAL (PUT/PATCH)
     * Sesuai F-05
     */
    public function update(Request $request, $id)
    {
        $schedule = PersonalSchedule::where('user_id', Auth::id())->find($id);

        if (!$schedule) {
            return response()->json([
                'message' => 'Jadwal pribadi tidak ditemukan',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'start_time' => 'sometimes|required|date',
            'end_time' => 'sometimes|required|date|after:start_time',
            'location' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        $schedule->update($validator->validated());

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
                            'remindable_type' => PersonalSchedule::class,
                            'minutes_before_start' => $minutes,
                            'status' => 'pending',
                        ]);
                    }
                }
            }
        }

        $schedule->load('reminders');

        return response()->json([
            'message' => 'Jadwal pribadi berhasil diperbarui',
            'data' => $schedule,
        ]);
    }

    /**
     * MENGHAPUS JADWAL (DELETE)
     * Sesuai F-05
     */
    public function destroy($id)
    {
        $schedule = PersonalSchedule::where('user_id', Auth::id())->find($id);

        if (!$schedule) {
            return response()->json([
                'message' => 'Jadwal pribadi tidak ditemukan',
            ], 404);
        }

        $schedule->delete();

        return response()->json([
            'message' => 'Jadwal pribadi berhasil dihapus',
        ]);
    }

    /**
     * Create multiple schedules based on repeat pattern.
     */
    private function createRepeatingSchedules($data, $repeatDays, $repeatCount)
    {
        $startTime = Carbon::parse($data['start_time']);
        $endTime = Carbon::parse($data['end_time']);

        return \Illuminate\Support\Facades\DB::transaction(function () use ($data, $repeatDays, $repeatCount, $startTime, $endTime) {
            $schedulesToInsert = [];
            $currentDate = $startTime->copy();

            // Loop through repeat count (weeks)
            for ($week = 0; $week < $repeatCount; $week++) {
                // For each selected day in the week
                foreach ($repeatDays as $dayOfWeek) {
                    // Calculate the date for this day in this week
                    $scheduleDate = $currentDate->copy()->startOfWeek()->addDays($dayOfWeek - 1);

                    // Skip if the date is in the past and not today
                    if ($scheduleDate->isPast() && !$scheduleDate->isToday()) {
                        continue;
                    }

                    // Create start and end datetime for this schedule
                    $scheduleStart = $scheduleDate->copy()
                        ->setTime($startTime->hour, $startTime->minute, $startTime->second);
                    $scheduleEnd = $scheduleDate->copy()
                        ->setTime($endTime->hour, $endTime->minute, $endTime->second);

                    $scheduleData = $data;
                    $scheduleData['start_time'] = $scheduleStart->toDateTimeString();
                    $scheduleData['end_time'] = $scheduleEnd->toDateTimeString();

                    // Remove auxiliary data
                    unset($scheduleData['repeat_days']);
                    unset($scheduleData['repeat_count']);
                    unset($scheduleData['reminders']);

                    $schedule = PersonalSchedule::create($scheduleData);

                    // Create reminders
                    if (isset($data['reminders']) && is_array($data['reminders'])) {
                        foreach ($data['reminders'] as $minutes) {
                            if ($minutes > 0) {
                                \App\Models\Reminder::create([
                                    'remindable_id' => $schedule->id,
                                    'remindable_type' => PersonalSchedule::class,
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
