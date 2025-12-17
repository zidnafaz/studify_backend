<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\DeviceToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class DeviceTokenTest extends TestCase
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
    public function user_can_register_device_token()
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/device-tokens', [
                'token' => 'fcm-token-123',
                'platform' => 'android',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('device_tokens', [
            'user_id' => $this->user->id,
            'token' => 'fcm-token-123',
            'platform' => 'android',
        ]);
    }

    #[Test]
    public function duplicate_token_updates_existing_record()
    {
        DeviceToken::factory()->create([
            'user_id' => $this->user->id,
            'token' => 'fcm-token-123',
            'platform' => 'android',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/device-tokens', [
                'token' => 'fcm-token-123',
                'platform' => 'ios', // Different platform
            ]);

        $response->assertStatus(200);

        // Should only have one record with updated platform
        $this->assertEquals(1, DeviceToken::where('token', 'fcm-token-123')->count());
        $this->assertDatabaseHas('device_tokens', [
            'token' => 'fcm-token-123',
            'platform' => 'ios',
        ]);
    }

    #[Test]
    public function token_is_required()
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/device-tokens', [
                'platform' => 'android',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['token']);
    }

    #[Test]
    public function user_can_have_multiple_device_tokens()
    {
        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/device-tokens', [
                'token' => 'fcm-token-android',
                'platform' => 'android',
            ]);

        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/device-tokens', [
                'token' => 'fcm-token-ios',
                'platform' => 'ios',
            ]);

        $this->assertEquals(2, $this->user->deviceTokens()->count());
    }

    #[Test]
    public function unauthenticated_user_cannot_register_token()
    {
        $response = $this->postJson('/api/device-tokens', [
            'token' => 'fcm-token-123',
        ]);

        $response->assertStatus(401);
    }
}
