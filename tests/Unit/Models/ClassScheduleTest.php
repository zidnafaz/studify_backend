<?php

namespace Tests\Unit\Models;

use App\Models\ClassSchedule;
use App\Models\Classroom;
use App\Models\Reminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ClassScheduleTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function class_schedule_belongs_to_classroom()
    {
        $classroom = Classroom::factory()->create();
        $schedule = ClassSchedule::factory()->create(['classroom_id' => $classroom->id]);

        $this->assertInstanceOf(Classroom::class, $schedule->classroom);
        $this->assertEquals($classroom->id, $schedule->classroom->id);
    }

    #[Test]
    public function class_schedule_has_many_reminders()
    {
        $classroom = Classroom::factory()->create();
        $schedule = ClassSchedule::factory()->create(['classroom_id' => $classroom->id]);
        
        Reminder::factory()->count(2)->create([
            'remindable_type' => ClassSchedule::class,
            'remindable_id' => $schedule->id,
        ]);

        $this->assertEquals(2, $schedule->reminders->count());
        $this->assertInstanceOf(Reminder::class, $schedule->reminders->first());
    }

    #[Test]
    public function class_schedule_soft_deletes()
    {
        $classroom = Classroom::factory()->create();
        $schedule = ClassSchedule::factory()->create(['classroom_id' => $classroom->id]);
        $scheduleId = $schedule->id;

        $schedule->delete();

        $this->assertSoftDeleted('class_schedules', ['id' => $scheduleId]);
    }
}
