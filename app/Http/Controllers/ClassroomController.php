<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\ClassSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class ClassroomController extends Controller
{
    /**
     * Display a listing of classrooms.
     */
    public function index()
    {
        $user = Auth::user();

        // Get classrooms where user is owner or member
        $classrooms = Classroom::where('owner_id', $user->id)
            ->orWhereHas('users', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with(['owner:id,name,email', 'users:id,name,email'])
            ->withCount('users')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'data' => $classrooms
        ]);
    }

    /**
     * Store a newly created classroom.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => __('messages.validation_error'),
                'errors' => $validator->errors()
            ], 422);
        }

        // Generate unique code
        $uniqueCode = $this->generateUniqueCode();

        $classroom = Classroom::create([
            'owner_id' => Auth::id(),
            'name' => $request->name,
            'description' => $request->description,
            'unique_code' => $uniqueCode,
        ]);

        // Automatically add owner as member
        $classroom->users()->attach(Auth::id());

        // Load relationships
        $classroom->load(['owner:id,name,email', 'users:id,name,email']);
        $classroom->loadCount('users');

        return response()->json([
            'message' => __('messages.classroom_created'),
            'data' => $classroom
        ], 201);
    }

    /**
     * Display the specified classroom.
     */
    public function show($id)
    {
        $classroom = Classroom::with([
            'owner:id,name,email',
            'users:id,name,email',
            'classSchedules:id,classroom_id,coordinator_1,coordinator_2,title,start_time,end_time,color',
            'classSchedules.reminders'
        ])
            ->withCount('users')
            ->find($id);

        if (!$classroom) {
            return response()->json([
                'message' => __('messages.classroom_not_found')
            ], 404);
        }

        // Check if user has access
        $user = Auth::user();
        if ($classroom->owner_id !== $user->id && !$classroom->users->contains($user->id)) {
            return response()->json([
                'message' => __('messages.unauthorized_access')
            ], 403);
        }

        // Add coordinator flag and schedules to users
        $classroom->users->transform(function ($user) use ($classroom) {
            // Get schedules where user is coordinator and group by title
            $coordinatorSchedules = $classroom->classSchedules
                ->filter(function ($schedule) use ($user) {
                    return $schedule->coordinator_1 == $user->id || $schedule->coordinator_2 == $user->id;
                })
                ->groupBy('title')
                ->map(function ($scheduleGroup) {
                    // Take only the first schedule of each title group
                    $firstSchedule = $scheduleGroup->first();
                    return [
                        'title' => $firstSchedule->title,
                        'color' => $firstSchedule->color,
                    ];
                })
                ->values();

            $user->is_coordinator = $coordinatorSchedules->isNotEmpty();
            $user->coordinator_schedules = $coordinatorSchedules;

            return $user;
        });

        return response()->json([
            'data' => $classroom
        ]);
    }

    /**
     * Update the specified classroom.
     */
    public function update(Request $request, $id)
    {
        $classroom = Classroom::find($id);

        if (!$classroom) {
            return response()->json([
                'message' => 'Classroom not found'
            ], 404);
        }

        // Only owner can update
        if ($classroom->owner_id !== Auth::id()) {
            return response()->json([
                'message' => 'Only classroom owner can update'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $classroom->update($request->only(['name', 'description']));
        $classroom->load(['owner:id,name,email', 'users:id,name,email']);
        $classroom->loadCount('users');

        return response()->json([
            'message' => 'Classroom updated successfully',
            'data' => $classroom
        ]);
    }

    /**
     * Remove the specified classroom.
     */
    public function destroy($id)
    {
        $classroom = Classroom::find($id);

        if (!$classroom) {
            return response()->json([
                'message' => 'Classroom not found'
            ], 404);
        }

        // Only owner can delete
        if ($classroom->owner_id != Auth::id()) {
            return response()->json([
                'message' => 'Only classroom owner can delete'
            ], 403);
        }

        $classroom->delete();

        return response()->json([
            'message' => 'Classroom deleted successfully'
        ]);
    }

    /**
     * Join a classroom using unique code.
     */
    public function join(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'unique_code' => 'required|string|size:8',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $classroom = Classroom::with('users:id,name,email')
            ->where('unique_code', $request->unique_code)
            ->first();

        if (!$classroom) {
            return response()->json([
                'message' => 'Classroom not found with this code'
            ], 404);
        }

        $user = Auth::user();

        // Check if already a member
        if ($classroom->users->contains($user->id)) {
            return response()->json([
                'message' => 'You are already a member of this classroom'
            ], 400);
        }

        // Add user to classroom
        $classroom->users()->attach($user->id);
        $classroom->load(['owner:id,name,email', 'users:id,name,email']);
        $classroom->loadCount('users');

        return response()->json([
            'message' => 'Successfully joined classroom',
            'data' => $classroom
        ]);
    }

    /**
     * Leave a classroom.
     */
    public function leave($id)
    {
        $classroom = Classroom::find($id);

        if (!$classroom) {
            return response()->json([
                'message' => 'Classroom not found'
            ], 404);
        }

        $user = Auth::user();

        // Owner cannot leave their own classroom
        if ($classroom->owner_id === $user->id) {
            return response()->json([
                'message' => 'Classroom owner cannot leave. Please transfer ownership first.'
            ], 400);
        }

        // Check if user is a member
        if (!$classroom->users->contains($user->id)) {
            return response()->json([
                'message' => 'You are not a member of this classroom'
            ], 400);
        }

        // Remove user from classroom
        $classroom->users()->detach($user->id);

        return response()->json([
            'message' => 'Successfully left classroom'
        ]);
    }

    /**
     * Remove a member from classroom (owner only).
     */
    public function removeMember(Request $request, $id)
    {
        $classroom = Classroom::find($id);

        if (!$classroom) {
            return response()->json([
                'message' => 'Classroom not found'
            ], 404);
        }

        // Only owner can remove members
        if ($classroom->owner_id !== Auth::id()) {
            return response()->json([
                'message' => 'Only classroom owner can remove members'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $userId = $request->user_id;

        // Cannot remove owner
        if ($classroom->owner_id === $userId) {
            return response()->json([
                'message' => 'Cannot remove classroom owner'
            ], 400);
        }

        // Check if user is a member
        if (!$classroom->users->contains($userId)) {
            return response()->json([
                'message' => 'User is not a member of this classroom'
            ], 400);
        }

        // Update all schedules where this user is coordinator - set back to owner
        ClassSchedule::where('classroom_id', $id)
            ->where(function ($query) use ($userId) {
                $query->where('coordinator_1', $userId)
                    ->orWhere('coordinator_2', $userId);
            })
            ->get()
            ->each(function ($schedule) use ($userId, $classroom) {
                if ($schedule->coordinator_1 == $userId) {
                    $schedule->coordinator_1 = $classroom->owner_id;
                }
                if ($schedule->coordinator_2 == $userId) {
                    $schedule->coordinator_2 = $classroom->owner_id;
                }
                $schedule->save();
            });

        // Remove user from classroom
        $classroom->users()->detach($userId);

        return response()->json([
            'message' => 'Member removed successfully'
        ]);
    }

    /**
     * Transfer ownership of classroom (owner only).
     */
    public function transferOwnership(Request $request, $id)
    {
        $classroom = Classroom::find($id);

        if (!$classroom) {
            return response()->json([
                'message' => 'Classroom not found'
            ], 404);
        }

        // Only owner can transfer ownership
        if ($classroom->owner_id !== Auth::id()) {
            return response()->json([
                'message' => 'Only classroom owner can transfer ownership'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'new_owner_id' => 'required|integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $newOwnerId = $request->new_owner_id;

        // Check if new owner is a member
        if (!$classroom->users->contains($newOwnerId)) {
            return response()->json([
                'message' => 'New owner must be a member of the classroom'
            ], 400);
        }

        // Transfer ownership
        $classroom->owner_id = $newOwnerId;
        $classroom->save();

        $classroom->load(['owner:id,name,email', 'users:id,name,email']);
        $classroom->loadCount('users');

        return response()->json([
            'message' => 'Ownership transferred successfully',
            'data' => $classroom
        ]);
    }

    /**
     * Generate a unique code for classroom.
     */
    private function generateUniqueCode()
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (Classroom::where('unique_code', $code)->exists());

        return $code;
    }
}
