<?php

namespace Tests\Unit\Models;

use App\Models\User;
use App\Models\Classroom;
use App\Models\PersonalSchedule;
use App\Models\DeviceToken;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function user_has_fillable_attributes()
    {
        $user = new User();
        $fillable = $user->getFillable();

        $this->assertContains('name', $fillable);
        $this->assertContains('email', $fillable);
        $this->assertContains('password', $fillable);
    }

    #[Test]
    public function user_has_hidden_attributes()
    {
        $user = User::factory()->create();
        $array = $user->toArray();

        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayNotHasKey('remember_token', $array);
    }

    #[Test]
    public function user_has_owned_classrooms_relationship()
    {
        $user = User::factory()->create();
        $classroom = Classroom::factory()->create(['owner_id' => $user->id]);

        $this->assertInstanceOf(Classroom::class, $user->ownedClassrooms->first());
        $this->assertEquals(1, $user->ownedClassrooms->count());
    }

    #[Test]
    public function user_has_classrooms_many_to_many_relationship()
    {
        $user = User::factory()->create();
        $classroom = Classroom::factory()->create();
        $classroom->users()->attach($user->id);

        $this->assertInstanceOf(Classroom::class, $user->classrooms->first());
        $this->assertEquals(1, $user->classrooms->count());
    }

    #[Test]
    public function user_has_personal_schedules_relationship()
    {
        $user = User::factory()->create();
        $schedule = PersonalSchedule::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(PersonalSchedule::class, $user->personalSchedules->first());
        $this->assertEquals(1, $user->personalSchedules->count());
    }

    #[Test]
    public function user_has_device_tokens_relationship()
    {
        $user = User::factory()->create();
        $token = DeviceToken::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(DeviceToken::class, $user->deviceTokens->first());
        $this->assertEquals(1, $user->deviceTokens->count());
    }

    #[Test]
    public function user_implements_jwt_subject()
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(\Tymon\JWTAuth\Contracts\JWTSubject::class, $user);
        $this->assertEquals($user->id, $user->getJWTIdentifier());
        $this->assertIsArray($user->getJWTCustomClaims());
    }

    #[Test]
    public function user_soft_deletes()
    {
        $user = User::factory()->create();
        $userId = $user->id;

        $user->delete();

        $this->assertSoftDeleted('users', ['id' => $userId]);
        $this->assertNotNull(User::withTrashed()->find($userId)->deleted_at);
    }

    #[Test]
    public function user_password_is_hashed_on_creation()
    {
        $plainPassword = 'password123';
        $user = User::factory()->create(['password' => $plainPassword]);

        $this->assertNotEquals($plainPassword, $user->password);
        $this->assertTrue(Hash::check($plainPassword, $user->password));
    }

    #[Test]
    public function user_email_must_be_unique()
    {
        User::factory()->create(['email' => 'test@example.com']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        User::factory()->create(['email' => 'test@example.com']);
    }
}
