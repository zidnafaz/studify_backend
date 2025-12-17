<?php

namespace Tests\Feature;

use App\Models\Classroom;
use App\Models\ClassSchedule;
use App\Models\PersonalSchedule;
use App\Models\Reminder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class ReminderTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_user_can_create_reminder_for_personal_schedule()
    {
        $user = User::factory()->create();
        $schedule = PersonalSchedule::factory()->create(['user_id' => $user->id]);
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/reminders', [
                'remindable_id' => $schedule->id,
                'remindable_type' => 'personal_schedule',
                'minutes_before_start' => 15,
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'minutes_before_start',
                    'status',
                    'remindable_id',
                    'remindable_type',
                ]
            ]);

        $this->assertDatabaseHas('reminders', [
            'remindable_id' => $schedule->id,
            'remindable_type' => PersonalSchedule::class,
            'minutes_before_start' => 15,
            'status' => 'pending',
        ]);
    }

    public function test_user_can_create_reminder_for_class_schedule()
    {
        $user = User::factory()->create();
        $classroom = Classroom::factory()->create(['owner_id' => $user->id]);
        $schedule = ClassSchedule::factory()->create(['classroom_id' => $classroom->id]);
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/reminders', [
                'remindable_id' => $schedule->id,
                'remindable_type' => 'class_schedule',
                'minutes_before_start' => 30,
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('reminders', [
            'remindable_id' => $schedule->id,
            'remindable_type' => ClassSchedule::class,
            'minutes_before_start' => 30,
        ]);
    }

    public function test_coordinator_can_create_reminder_for_class_schedule()
    {
        $owner = User::factory()->create();
        $coordinator = User::factory()->create();
        $classroom = Classroom::factory()->create(['owner_id' => $owner->id]);
        $classroom->users()->attach($coordinator->id);

        $schedule = ClassSchedule::factory()->create([
            'classroom_id' => $classroom->id,
            'coordinator_1' => $coordinator->id,
        ]);

        $token = JWTAuth::fromUser($coordinator);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/reminders', [
                'remindable_id' => $schedule->id,
                'remindable_type' => 'class_schedule',
                'minutes_before_start' => 30,
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('reminders', [
            'remindable_id' => $schedule->id,
            'remindable_type' => ClassSchedule::class,
            'minutes_before_start' => 30,
        ]);
    }

    public function test_user_cannot_create_reminder_for_others_personal_schedule()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $schedule = PersonalSchedule::factory()->create(['user_id' => $otherUser->id]);
        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/reminders', [
                'remindable_id' => $schedule->id,
                'remindable_type' => 'personal_schedule',
                'minutes_before_start' => 15,
            ]);

        $response->assertStatus(403);
    }

    public function test_regular_user_cannot_create_reminder_for_class_schedule()
    {
        $owner = User::factory()->create();
        $user = User::factory()->create();
        $classroom = Classroom::factory()->create(['owner_id' => $owner->id]);
        $classroom->users()->attach($user->id);
        $schedule = ClassSchedule::factory()->create(['classroom_id' => $classroom->id]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/reminders', [
                'remindable_id' => $schedule->id,
                'remindable_type' => 'class_schedule',
                'minutes_before_start' => 30,
            ]);

        $response->assertStatus(403);
    }

    public function test_user_can_delete_reminder()
    {
        $user = User::factory()->create();
        $schedule = PersonalSchedule::factory()->create(['user_id' => $user->id]);
        $reminder = Reminder::factory()->create([
            'remindable_id' => $schedule->id,
            'remindable_type' => PersonalSchedule::class,
        ]);

        $token = JWTAuth::fromUser($user);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->deleteJson("/api/reminders/{$reminder->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('reminders', ['id' => $reminder->id]);
    }
}
