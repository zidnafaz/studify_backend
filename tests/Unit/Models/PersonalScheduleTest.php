<?php

namespace Tests\Unit\Models;

use App\Models\PersonalSchedule;
use App\Models\User;
use App\Models\Reminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PersonalScheduleTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function personal_schedule_belongs_to_user()
    {
        $user = User::factory()->create();
        $schedule = PersonalSchedule::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $schedule->user);
        $this->assertEquals($user->id, $schedule->user->id);
    }

    #[Test]
    public function personal_schedule_has_many_reminders()
    {
        $user = User::factory()->create();
        $schedule = PersonalSchedule::factory()->create(['user_id' => $user->id]);
        
        Reminder::factory()->count(2)->create([
            'remindable_type' => PersonalSchedule::class,
            'remindable_id' => $schedule->id,
        ]);

        $this->assertEquals(2, $schedule->reminders->count());
        $this->assertInstanceOf(Reminder::class, $schedule->reminders->first());
    }

    #[Test]
    public function personal_schedule_soft_deletes()
    {
        $user = User::factory()->create();
        $schedule = PersonalSchedule::factory()->create(['user_id' => $user->id]);
        $scheduleId = $schedule->id;

        $schedule->delete();

        $this->assertSoftDeleted('personal_schedules', ['id' => $scheduleId]);
    }
}
