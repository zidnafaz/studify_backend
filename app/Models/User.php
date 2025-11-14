<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the classrooms owned by the user.
     */
    public function ownedClassrooms()
    {
        return $this->hasMany(Classroom::class, 'owner_id');
    }

    /**
     * Get the classrooms the user is a member of.
     */
    public function classrooms()
    {
        return $this->belongsToMany(Classroom::class, 'classroom_user')
            ->withTimestamps()
            ->withPivot('deleted_at');
    }

    /**
     * Get the class schedules where the user is coordinator.
     */
    public function coordinatedSchedules()
    {
        return $this->hasMany(ClassSchedule::class, 'coordinator_id');
    }

    /**
     * Get the personal schedules for the user.
     */
    public function personalSchedules()
    {
        return $this->hasMany(PersonalSchedule::class);
    }

    /**
     * Get the device tokens for the user.
     */
    public function deviceTokens()
    {
        return $this->hasMany(DeviceToken::class);
    }
}
