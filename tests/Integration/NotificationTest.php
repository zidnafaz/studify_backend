<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->token = JWTAuth::fromUser($this->user);
    }

    #[Test]
    public function user_can_fetch_their_notifications()
    {
        Notification::factory()->count(5)->create(['user_id' => $this->user->id]);
        Notification::factory()->count(3)->create(); // Other user's notifications

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/notifications');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'title', 'body', 'is_read', 'created_at']
                ]
            ]);
    }

    #[Test]
    public function user_can_mark_notification_as_read()
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'is_read' => false,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->patchJson("/api/notifications/{$notification->id}/read");

        $response->assertStatus(200);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'is_read' => true,
        ]);
    }

    #[Test]
    public function user_can_mark_all_notifications_as_read()
    {
        Notification::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'is_read' => false,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->patchJson('/api/notifications/read-all');

        $response->assertStatus(200);

        $this->assertEquals(0, Notification::where('user_id', $this->user->id)
            ->where('is_read', false)
            ->count());
    }

    #[Test]
    public function user_cannot_mark_other_users_notification_as_read()
    {
        $otherUser = User::factory()->create();
        $notification = Notification::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->patchJson("/api/notifications/{$notification->id}/read");

        $response->assertStatus(404);
    }

    #[Test]
    public function notifications_are_ordered_by_newest_first()
    {
        $old = Notification::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDays(5),
        ]);

        $new = Notification::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/notifications');

        $data = $response->json('data');
        $this->assertEquals($new->id, $data[0]['id']);
        $this->assertEquals($old->id, $data[1]['id']);
    }

    #[Test]
    public function unauthenticated_user_cannot_access_notifications()
    {
        $response = $this->getJson('/api/notifications');
        $response->assertStatus(401);
    }
}
