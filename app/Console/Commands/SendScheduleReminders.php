<?php

namespace App\Console\Commands;

use App\Models\Reminder;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class SendScheduleReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schedule:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send push notifications for due schedule reminders';

    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for due reminders...');

        $reminders = Reminder::where('status', 'pending')->with('remindable')->get();

        $count = 0;

        foreach ($reminders as $reminder) {
            $schedule = $reminder->remindable;

            if (!$schedule) {
                continue;
            }

            $startTime = Carbon::parse($schedule->start_time);
            $reminderTime = $startTime->subMinutes($reminder->minutes_before_start);

            // Check if it's time to remind (and we haven't missed it by too much, e.g. 1 hour)
            if (now()->greaterThanOrEqualTo($reminderTime) && now()->lessThan($reminderTime->addHour())) {
                $this->sendNotification($reminder, $schedule);
                $count++;
            } elseif (now()->greaterThanOrEqualTo($reminderTime->addHour())) {
                // Mark as missed/failed if it's too late
                $reminder->update(['status' => 'failed']);
                $this->warn("Reminder {$reminder->id} missed.");
            }
        }

        $this->info("Sent {$count} reminders.");
    }

    private function sendNotification(Reminder $reminder, $schedule)
    {
        // Get user(s) to notify
        $users = collect();

        if ($reminder->remindable_type === 'App\Models\PersonalSchedule') {
            $users->push($schedule->user);
        } elseif ($reminder->remindable_type === 'App\Models\ClassSchedule') {
            $classroom = $schedule->classroom;
            if ($classroom) {
                // Get all members
                $users = $classroom->users;
                if (!$users->contains('id', $classroom->owner_id)) {
                    $users->push($classroom->owner);
                }
            }
        }

        $title = $schedule->title;
        // Only ClassSchedule has lecturer
        if ($reminder->remindable_type === 'App\Models\ClassSchedule' && !empty($schedule->lecturer)) {
            $title .= " - " . $schedule->lecturer;
        }

        $startTime = Carbon::parse($schedule->start_time)->format('H:i');
        $endTime = Carbon::parse($schedule->end_time)->format('H:i');
        $body = "{$startTime} - {$endTime}";

        if (!empty($schedule->location)) {
            $body .= " | " . $schedule->location;
        }

        $data = [
            'schedule_id' => (string) $schedule->id,
            'type' => $reminder->remindable_type,
        ];

        $this->notificationService->sendToUsers($users, $title, $body, $data);

        $reminder->update(['status' => 'sent']);
    }
}
