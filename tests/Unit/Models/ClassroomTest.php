<?php

namespace Tests\Unit\Models;

use App\Models\User;
use App\Models\Classroom;
use App\Models\ClassSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ClassroomTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function classroom_belongs_to_owner()
    {
        $owner = User::factory()->create();
        $classroom = Classroom::factory()->create(['owner_id' => $owner->id]);

        $this->assertInstanceOf(User::class, $classroom->owner);
        $this->assertEquals($owner->id, $classroom->owner->id);
    }

    #[Test]
    public function classroom_has_many_users()
    {
        $classroom = Classroom::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $classroom->users()->attach([$user1->id, $user2->id]);

        $this->assertEquals(2, $classroom->users->count());
    }

    #[Test]
    public function classroom_has_many_schedules()
    {
        $classroom = Classroom::factory()->create();
        ClassSchedule::factory()->count(3)->create(['classroom_id' => $classroom->id]);

        $this->assertEquals(3, $classroom->classSchedules->count());
    }

    #[Test]
    public function classroom_unique_code_must_be_unique()
    {
        Classroom::factory()->create(['unique_code' => 'ABC123']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Classroom::factory()->create(['unique_code' => 'ABC123']);
    }

    #[Test]
    public function classroom_soft_deletes()
    {
        $classroom = Classroom::factory()->create();
        $classroomId = $classroom->id;

        $classroom->delete();

        $this->assertSoftDeleted('classrooms', ['id' => $classroomId]);
    }

    #[Test]
    public function deleting_classroom_deletes_pivot_records()
    {
        $classroom = Classroom::factory()->create();
        $user = User::factory()->create();
        $classroom->users()->attach($user->id);

        $this->assertDatabaseHas('classroom_user', [
            'classroom_id' => $classroom->id,
            'user_id' => $user->id,
        ]);

        $classroom->delete();

        // Cascade delete should remove pivot records
        $this->assertDatabaseMissing('classroom_user', [
            'classroom_id' => $classroom->id,
            'user_id' => $user->id,
            'deleted_at' => null,
        ]);
    }
}
