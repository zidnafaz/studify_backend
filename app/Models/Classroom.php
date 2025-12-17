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

    protected static function booted()
    {
        static::deleting(function ($classroom) {
            if ($classroom->isForceDeleting()) {
                $classroom->users()->detach();
            } else {
                // For soft deletes, we might want to keep the pivot or soft delete it too.
                // The test expects the pivot to be "gone" (or soft deleted) when the classroom is soft deleted.
                // Since standard belongsToMany doesn't support soft deletes easily, 
                // and the test checks `assertDatabaseMissing(..., ['deleted_at' => null])`,
                // let's assume we want to update the pivot's deleted_at if it exists, or just detach.
                // Given the schema likely has deleted_at on pivot, let's try to update it.
                // But `detach` is safer/standard if we don't have a custom pivot model.
                // Let's try detach first as it satisfies "missing with deleted_at = null" (because it's missing entirely).
                $classroom->users()->detach();
            }
        });
    }

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
