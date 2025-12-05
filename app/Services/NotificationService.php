<?php

namespace App\Services;

use App\Models\User;
use App\Models\Notification as NotificationModel;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    protected $messaging;

    public function __construct(Messaging $messaging)
    {
        $this->messaging = $messaging;
    }

    /**
     * Send notification to a specific user.
     *
     * @param User $user
     * @param string $title
     * @param string $body
     * @param array $data
     * @return void
     */
    public function sendToUser(User $user, string $title, string $body, array $data = [])
    {
        // Store notification in database
        NotificationModel::create([
            'user_id' => $user->id,
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ]);

        // Send FCM notification
        $tokens = $user->deviceTokens;

        if ($tokens->isEmpty()) {
            return;
        }

        foreach ($tokens as $token) {
            try {
                $message = CloudMessage::withTarget('token', $token->token)
                    ->withNotification(Notification::create($title, $body))
                    ->withData($data);

                $this->messaging->send($message);
            } catch (\Throwable $e) {
                Log::error("Failed to send FCM to token {$token->id}: " . $e->getMessage());
            }
        }
    }

    /**
     * Send notification to multiple users.
     *
     * @param iterable $users
     * @param string $title
     * @param string $body
     * @param array $data
     * @return void
     */
    public function sendToUsers($users, string $title, string $body, array $data = [])
    {
        foreach ($users as $user) {
            $this->sendToUser($user, $title, $body, $data);
        }
    }
}
