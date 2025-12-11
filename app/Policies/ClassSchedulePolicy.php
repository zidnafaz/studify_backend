<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ClassSchedule;
use App\Models\Classroom;
use Illuminate\Support\Facades\DB;

class ClassSchedulePolicy
{
    /**
     * Determine if the user can view class schedules.
     * Izinkan jika user_id ada di classroom_user (member kelas) ATAU user_id == owner_id
     *
     * NOTE: Owner HARUS join sebagai member untuk consistency,
     * tapi tetap diberi akses langsung untuk backward compatibility
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Classroom  $classroom
     * @return bool
     */
    public function view(User $user, Classroom $classroom)
    {
        // Owner always has access (untuk case belum join sebagai member)
        if ((int)$user->id === (int)$classroom->owner_id) {
            return true;
        }

        // Check if user is member of the classroom
        return DB::table('classroom_user')
            ->where('classroom_id', $classroom->id)
            ->where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->exists();
    }

    /**
     * Determine if the user can create class schedules.
     * Izinkan jika user_id == classrooms.owner_id (pemilik kelas)
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Classroom  $classroom
     * @return bool
     */
    public function create(User $user, Classroom $classroom)
    {
        return (int)$user->id === (int)$classroom->owner_id;
    }

    /**
     * Determine if the user can update class schedules.
     * Izinkan jika user_id == classrooms.owner_id ATAU
     * user_id == class_schedules.coordinator_1/2
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ClassSchedule  $schedule
     * @param  \App\Models\Classroom  $classroom
     * @return bool
     */
    public function update(User $user, ClassSchedule $schedule, Classroom $classroom)
    {
        return (int)$user->id === (int)$classroom->owner_id
            || (int)$user->id === (int)$schedule->coordinator_1
            || (int)$user->id === (int)$schedule->coordinator_2;
    }

    /**
     * Determine if the user can delete class schedules.
     * Izinkan jika user_id == classrooms.owner_id ATAU
     * user_id == class_schedules.coordinator_1/2
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\ClassSchedule  $schedule
     * @param  \App\Models\Classroom  $classroom
     * @return bool
     */
    public function delete(User $user, ClassSchedule $schedule, Classroom $classroom)
    {
        return (int)$user->id === (int)$classroom->owner_id
            || (int)$user->id === (int)$schedule->coordinator_1
            || (int)$user->id === (int)$schedule->coordinator_2;
    }
}
