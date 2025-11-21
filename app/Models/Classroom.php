<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Classroom extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'owner_id',
        'name',
        'unique_code',
        'description',
    ];

    /**
     * Get the owner of the classroom.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * Get the users in the classroom.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'classroom_user')
            ->withTimestamps()
            ->withPivot('deleted_at')
            ->wherePivotNull('deleted_at');
    }

    /**
     * Get the class schedules for the classroom.
     */
    public function classSchedules(): HasMany
    {
        return $this->hasMany(ClassSchedule::class);
    }
}
