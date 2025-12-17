<?php

namespace Tests\Unit\Models;

use App\Models\Reminder;
use App\Models\PersonalSchedule;
use App\Models\ClassSchedule;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReminderTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function reminder_can_belong_to_personal_schedule()
    {
        $user = User::factory()->create();
        $schedule = PersonalSchedule::factory()->create(['user_id' => $user->id]);
        $reminder = Reminder::factory()->create([
            'remindable_type' => PersonalSchedule::class,
            'remindable_id' => $schedule->id,
        ]);

        $this->assertInstanceOf(PersonalSchedule::class, $reminder->remindable);
        $this->assertEquals($schedule->id, $reminder->remindable->id);
    }

    #[Test]
    public function reminder_can_belong_to_class_schedule()
    {
        $classroom = Classroom::factory()->create();
        $schedule = ClassSchedule::factory()->create(['classroom_id' => $classroom->id]);
        $reminder = Reminder::factory()->create([
            'remindable_type' => ClassSchedule::class,
            'remindable_id' => $schedule->id,
        ]);

        $this->assertInstanceOf(ClassSchedule::class, $reminder->remindable);
        $this->assertEquals($schedule->id, $reminder->remindable->id);
    }

    #[Test]
    public function reminder_has_default_values()
    {
        $user = User::factory()->create();
        $schedule = PersonalSchedule::factory()->create(['user_id' => $user->id]);
        $reminder = Reminder::create([
            'remindable_type' => PersonalSchedule::class,
            'remindable_id' => $schedule->id,
        ]);

        $this->assertEquals(30, $reminder->minutes_before_start);
        $this->assertEquals('pending', $reminder->status);
    }

    #[Test]
    public function reminder_status_can_be_updated()
    {
        $reminder = Reminder::factory()->create(['status' => 'pending']);

        $reminder->update(['status' => 'sent']);

        $this->assertEquals('sent', $reminder->fresh()->status);
    }

    #[Test]
    public function reminder_soft_deletes()
    {
        $reminder = Reminder::factory()->create();
        $reminderId = $reminder->id;

        $reminder->delete();

        $this->assertSoftDeleted('reminders', ['id' => $reminderId]);
    }
}
