<?php

namespace Tests\Feature;

use App\Models\PersonalSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class PersonalScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
        $this->token = JWTAuth::fromUser($this->user);
    }

    protected function authHeaders(): array
    {
        return [
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ];
    }

    public function test_user_can_list_only_their_personal_schedules(): void
    {
        PersonalSchedule::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        PersonalSchedule::factory()->create([
            'user_id' => $this->otherUser->id,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/personal-schedules');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_user_can_create_personal_schedule(): void
    {
        $payload = [
            'title' => 'Belajar Flutter',
            'start_time' => now()->addDay()->toDateTimeString(),
            'end_time' => now()->addDay()->addHour()->toDateTimeString(),
            'location' => 'Perpustakaan',
            'description' => 'Materi state management',
            'color' => '#10B981',
        ];

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/personal-schedules', $payload);

        $response->assertCreated()
            ->assertJson([
                'message' => 'Jadwal pribadi berhasil dibuat',
                'data' => [
                    'title' => 'Belajar Flutter',
                    'location' => 'Perpustakaan',
                ],
            ]);

        $this->assertDatabaseHas('personal_schedules', [
            'title' => 'Belajar Flutter',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_validation_fails_for_invalid_color(): void
    {
        $payload = [
            'title' => 'Belajar Flutter',
            'start_time' => now()->addDay()->toDateTimeString(),
            'end_time' => now()->addDay()->addHour()->toDateTimeString(),
            'color' => '#12345',
        ];

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/personal-schedules', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('color');
    }

    public function test_user_can_view_their_schedule_detail(): void
    {
        $schedule = PersonalSchedule::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/personal-schedules/{$schedule->id}");

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'id' => $schedule->id,
                ],
            ]);
    }

    public function test_user_cannot_view_other_users_schedule(): void
    {
        $schedule = PersonalSchedule::factory()->create([
            'user_id' => $this->otherUser->id,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/personal-schedules/{$schedule->id}");

        $response->assertNotFound();
    }

    public function test_user_can_update_personal_schedule(): void
    {
        $schedule = PersonalSchedule::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Belajar Flutter',
        ]);

        $payload = [
            'title' => 'Belajar Laravel',
            'start_time' => now()->addDays(2)->toDateTimeString(),
            'end_time' => now()->addDays(2)->addHours(2)->toDateTimeString(),
            'color' => '#2563EB',
        ];

        $response = $this->withHeaders($this->authHeaders())
            ->putJson("/api/personal-schedules/{$schedule->id}", $payload);

        $response->assertOk()
            ->assertJson([
                'message' => 'Jadwal pribadi berhasil diperbarui',
                'data' => [
                    'title' => 'Belajar Laravel',
                    'color' => '#2563EB',
                ],
            ]);

        $this->assertDatabaseHas('personal_schedules', [
            'id' => $schedule->id,
            'title' => 'Belajar Laravel',
        ]);
    }

    public function test_user_can_delete_personal_schedule(): void
    {
        $schedule = PersonalSchedule::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->deleteJson("/api/personal-schedules/{$schedule->id}");

        $response->assertOk()
            ->assertJson([
                'message' => 'Jadwal pribadi berhasil dihapus',
            ]);

        $this->assertSoftDeleted('personal_schedules', [
            'id' => $schedule->id,
        ]);
    }
    public function test_user_can_create_repeating_personal_schedule_with_reminders(): void
    {
        $payload = [
            'title' => 'Repeating Schedule',
            'start_time' => now()->addDay()->toDateTimeString(),
            'end_time' => now()->addDay()->addHour()->toDateTimeString(),
            'location' => 'Room A',
            'description' => 'Weekly meeting',
            'color' => '#10B981',
            'repeat_days' => [now()->addDay()->dayOfWeekIso], // Repeat on the same day next week
            'repeat_count' => 3, // Create for 3 weeks
            'reminders' => [15],
        ];

        $response = $this->withHeaders($this->authHeaders())
            ->postJson('/api/personal-schedules', $payload);

        $response->assertCreated();

        // Check if main schedule created
        $this->assertDatabaseHas('personal_schedules', [
            'title' => 'Repeating Schedule',
            'user_id' => $this->user->id,
        ]);

        // Check if repeated schedules created (original + 3 weeks = 4 schedules total)
        $count = PersonalSchedule::where('title', 'Repeating Schedule')
            ->where('user_id', $this->user->id)
            ->count();

        $this->assertGreaterThanOrEqual(2, $count);

        // Check if reminders created
        $schedule = PersonalSchedule::where('title', 'Repeating Schedule')
            ->where('user_id', $this->user->id)
            ->first();

        $this->assertDatabaseHas('reminders', [
            'remindable_id' => $schedule->id,
            'remindable_type' => PersonalSchedule::class,
            'minutes_before_start' => 15,
        ]);
    }
}

