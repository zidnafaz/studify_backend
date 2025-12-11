<?php

namespace App\Http\Controllers;

use App\Models\Reminder;
use App\Models\ClassSchedule;
use App\Models\PersonalSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ReminderController extends Controller
{
    /**
     * Store a newly created reminder in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'remindable_id' => 'required|integer',
            'remindable_type' => 'required|string|in:class_schedule,personal_schedule',
            'minutes_before_start' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => __('messages.validation_errors'),
                'errors' => $validator->errors(),
            ], 422);
        }

        $remindableType = $request->remindable_type === 'class_schedule'
            ? ClassSchedule::class
            : PersonalSchedule::class;

        $remindable = $remindableType::find($request->remindable_id);

        if (!$remindable) {
            return response()->json(['message' => __('messages.schedule_not_found')], 404);
        }

        // Authorization Check
        $this->authorizeAction($remindable);

        $reminder = Reminder::create([
            'remindable_id' => $request->remindable_id,
            'remindable_type' => $remindableType,
            'minutes_before_start' => $request->minutes_before_start,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => __('messages.reminder_created'),
            'data' => $reminder,
        ], 201);
    }

    /**
     * Update the specified reminder in storage.
     */
    public function update(Request $request, $id)
    {
        $reminder = Reminder::find($id);

        if (!$reminder) {
            return response()->json(['message' => __('messages.reminder_not_found')], 404);
        }

        // Authorization Check
        $this->authorizeAction($reminder->remindable);

        $validator = Validator::make($request->all(), [
            'minutes_before_start' => 'integer|min:1',
            'status' => 'in:pending,sent',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => __('messages.validation_errors'),
                'errors' => $validator->errors(),
            ], 422);
        }

        $reminder->update($request->only(['minutes_before_start', 'status']));

        return response()->json([
            'message' => __('messages.reminder_updated'),
            'data' => $reminder,
        ]);
    }

    /**
     * Remove the specified reminder from storage.
     */
    public function destroy($id)
    {
        $reminder = Reminder::find($id);

        if (!$reminder) {
            return response()->json(['message' => __('messages.reminder_not_found')], 404);
        }

        // Authorization Check
        $this->authorizeAction($reminder->remindable);

        $reminder->delete();

        return response()->json(['message' => __('messages.reminder_deleted')], 200);
    }

    /**
     * Helper to authorize actions based on schedule type.
     */
    private function authorizeAction($schedule)
    {
        $user = Auth::user();

        if ($schedule instanceof PersonalSchedule) {
            if ($schedule->user_id !== $user->id) {
                abort(403, 'Unauthorized action.');
            }
        } elseif ($schedule instanceof ClassSchedule) {
            // Check if user is owner or coordinator
            $isOwner = $schedule->classroom->owner_id === $user->id;
            $isCoordinator = $schedule->coordinator_1 === $user->id || $schedule->coordinator_2 === $user->id;

            if (!$isOwner && !$isCoordinator) {
                abort(403, 'Unauthorized action. Only Owner or Coordinator can manage reminders.');
            }
        }
    }
}
