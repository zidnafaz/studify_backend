<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Models\DeviceToken;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    /** @var Messaging&MockInterface */
    protected $messagingMock;
    protected $notificationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->messagingMock = $this->mock(Messaging::class);
        $this->notificationService = new NotificationService($this->messagingMock);
    }

    #[Test]
    public function it_can_send_notification_to_single_user()
    {
        $user = User::factory()->create();
        $deviceToken = DeviceToken::factory()->create([
            'user_id' => $user->id,
            'token' => 'test-fcm-token-123'
        ]);

        $this->messagingMock
            ->shouldReceive('send')
            ->once()
            ->andReturn(['success' => true]);

        $this->notificationService->sendToUser(
            $user,
            'Test Title',
            'Test Body',
            ['key' => 'value']
        );

        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'title' => 'Test Title',
            'body' => 'Test Body',
        ]);
    }

    #[Test]
    public function it_can_send_notification_to_multiple_users()
    {
        $users = User::factory()->count(3)->create();

        foreach ($users as $user) {
            DeviceToken::factory()->create([
                'user_id' => $user->id,
                'token' => 'fcm-token-' . $user->id
            ]);
        }

        $this->messagingMock
            ->shouldReceive('send')
            ->times(3)
            ->andReturn(['success' => true]);

        $this->notificationService->sendToUsers(
            $users,
            'Test Title',
            'Test Body'
        );

        foreach ($users as $user) {
            $this->assertDatabaseHas('notifications', [
                'user_id' => $user->id,
                'title' => 'Test Title',
                'body' => 'Test Body',
            ]);
        }
    }
}
