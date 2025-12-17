<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Reminder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'remindable_id',
        'remindable_type',
        'minutes_before_start',
        'status',
    ];

    protected $casts = [
        'remindable_id' => 'integer',
        'minutes_before_start' => 'integer',
    ];

    protected $attributes = [
        'minutes_before_start' => 30,
        'status' => 'pending',
    ];

    /**
     * Get the parent remindable model (ClassSchedule or PersonalSchedule).
     */
    public function remindable(): MorphTo
    {
        return $this->morphTo();
    }
}
