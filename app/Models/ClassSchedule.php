<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ClassSchedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'classroom_id',
        'coordinator_1',
        'coordinator_2',
        'title',
        'start_time',
        'end_time',
        'location',
        'lecturer',
        'description',
        'color',
    ];

    protected $casts = [
        'classroom_id' => 'integer',
        'coordinator_1' => 'integer',
        'coordinator_2' => 'integer',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    /**
     * Get the classroom that owns the schedule.
     */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    /**
     * Get the coordinator 1 of this schedule.
     */
    public function coordinator1(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coordinator_1');
    }

    /**
     * Get the coordinator 2 of this schedule.
     */
    public function coordinator2(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coordinator_2');
    }

    /**
     * Get all reminders for this schedule.
     */
    public function reminders(): MorphMany
    {
        return $this->morphMany(Reminder::class, 'remindable');
    }
}
