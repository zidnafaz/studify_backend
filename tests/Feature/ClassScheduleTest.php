<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Classroom;
use App\Models\ClassSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class ClassScheduleTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $owner;
    protected $member;
    protected $nonMember;
    protected $classroom;
    protected $token;

    /**
     * Setup test environment
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Create users
        $this->owner = User::factory()->create();
        $this->member = User::factory()->create();
        $this->nonMember = User::factory()->create();

        // Create classroom
        $this->classroom = Classroom::factory()->create([
            'owner_id' => $this->owner->id,
        ]);

        // Add owner as member (best practice untuk Opsi 2: Owner Transferable)
        // Owner HARUS jadi member juga untuk consistency
        $this->classroom->users()->attach($this->owner->id);
        
        // Add regular member to classroom
        $this->classroom->users()->attach($this->member->id);

        // Generate JWT token for owner
        $this->token = JWTAuth::fromUser($this->owner);
    }

    /**
     * Test: Member can list class schedules
     */
    public function test_member_can_list_class_schedules()
    {
        // Create some schedules
        ClassSchedule::factory()->count(3)->create([
            'classroom_id' => $this->classroom->id,
        ]);

        $memberToken = JWTAuth::fromUser($this->member);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $memberToken,
        ])->getJson("/api/classrooms/{$this->classroom->id}/schedules");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'classroom_id',
                        'title',
                        'start_time',
                        'end_time',
                        'location',
                        'lecturer',
                        'description',
                        'color',
                    ]
                ]
            ])
            ->assertJsonCount(3, 'data');
    }

    /**
     * Test: Non-member cannot list class schedules
     */
    public function test_non_member_cannot_list_class_schedules()
    {
        $nonMemberToken = JWTAuth::fromUser($this->nonMember);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $nonMemberToken,
        ])->getJson("/api/classrooms/{$this->classroom->id}/schedules");

        $response->assertStatus(403);
    }

    /**
     * Test: Unauthenticated user cannot access schedules
     */
    public function test_unauthenticated_user_cannot_access_schedules()
    {
        $response = $this->getJson("/api/classrooms/{$this->classroom->id}/schedules");

        $response->assertStatus(401);
    }

    /**
     * Test: Owner can create class schedule
     */
    public function test_owner_can_create_class_schedule()
    {
        $coordinator = User::factory()->create();

        $scheduleData = [
            'title' => 'Pemrograman Web',
            'start_time' => '2025-11-20 08:00:00',
            'end_time' => '2025-11-20 10:00:00',
            'location' => 'Ruang 301',
            'lecturer' => 'Dr. John Doe',
            'description' => 'Pertemuan ke-5: RESTful API',
            'color' => '#5CD9C1',
            'coordinator_1' => $coordinator->id,
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson("/api/classrooms/{$this->classroom->id}/schedules", $scheduleData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'classroom_id',
                    'title',
                    'start_time',
                    'end_time',
                    'location',
                    'lecturer',
                    'description',
                    'color',
                    'coordinator_1',
                ]
            ]);

        $this->assertDatabaseHas('class_schedules', [
            'classroom_id' => $this->classroom->id,
            'title' => 'Pemrograman Web',
            'location' => 'Ruang 301',
        ]);
    }

    /**
     * Test: Member cannot create class schedule (only owner can)
     */
    public function test_member_cannot_create_class_schedule()
    {
        $memberToken = JWTAuth::fromUser($this->member);

        $scheduleData = [
            'title' => 'Test Schedule',
            'start_time' => '2025-11-20 08:00:00',
            'end_time' => '2025-11-20 10:00:00',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $memberToken,
        ])->postJson("/api/classrooms/{$this->classroom->id}/schedules", $scheduleData);

        $response->assertStatus(403);
    }

    /**
     * Test: Validation fails with invalid data
     */
    public function test_create_schedule_validation_fails_with_invalid_data()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson("/api/classrooms/{$this->classroom->id}/schedules", [
            'title' => '',
            'start_time' => 'invalid-date',
            'end_time' => '2025-11-20 10:00:00',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors'
            ]);
    }

    /**
     * Test: End time must be after start time
     */
    public function test_end_time_must_be_after_start_time()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson("/api/classrooms/{$this->classroom->id}/schedules", [
            'title' => 'Test Schedule',
            'start_time' => '2025-11-20 10:00:00',
            'end_time' => '2025-11-20 08:00:00', // Earlier than start
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_time']);
    }

    /**
     * Test: Member can view schedule detail
     */
    public function test_member_can_view_schedule_detail()
    {
        $schedule = ClassSchedule::factory()->create([
            'classroom_id' => $this->classroom->id,
        ]);

        $memberToken = JWTAuth::fromUser($this->member);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $memberToken,
        ])->getJson("/api/classrooms/{$this->classroom->id}/schedules/{$schedule->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'classroom_id',
                    'title',
                    'start_time',
                    'end_time',
                ]
            ])
            ->assertJson([
                'data' => [
                    'id' => $schedule->id,
                    'title' => $schedule->title,
                ]
            ]);
    }

    /**
     * Test: Non-member cannot view schedule detail
     */
    public function test_non_member_cannot_view_schedule_detail()
    {
        $schedule = ClassSchedule::factory()->create([
            'classroom_id' => $this->classroom->id,
        ]);

        $nonMemberToken = JWTAuth::fromUser($this->nonMember);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $nonMemberToken,
        ])->getJson("/api/classrooms/{$this->classroom->id}/schedules/{$schedule->id}");

        $response->assertStatus(403);
    }

    /**
     * Test: Owner can update class schedule
     */
    public function test_owner_can_update_class_schedule()
    {
        $schedule = ClassSchedule::factory()->create([
            'classroom_id' => $this->classroom->id,
            'title' => 'Old Title',
        ]);

        $updateData = [
            'title' => 'Updated Title',
            'location' => 'Updated Location',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson("/api/classrooms/{$this->classroom->id}/schedules/{$schedule->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Jadwal kelas berhasil diperbarui',
                'data' => [
                    'title' => 'Updated Title',
                    'location' => 'Updated Location',
                ]
            ]);

        $this->assertDatabaseHas('class_schedules', [
            'id' => $schedule->id,
            'title' => 'Updated Title',
        ]);
    }

    /**
     * Test: Coordinator can update class schedule
     */
    public function test_coordinator_can_update_class_schedule()
    {
        $coordinator = User::factory()->create();

        $schedule = ClassSchedule::factory()->create([
            'classroom_id' => $this->classroom->id,
            'coordinator_1' => $coordinator->id,
            'title' => 'Old Title',
        ]);

        $coordinatorToken = JWTAuth::fromUser($coordinator);

        $updateData = [
            'title' => 'Updated by Coordinator',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $coordinatorToken,
        ])->putJson("/api/classrooms/{$this->classroom->id}/schedules/{$schedule->id}", $updateData);

        $response->assertStatus(200);

        $this->assertDatabaseHas('class_schedules', [
            'id' => $schedule->id,
            'title' => 'Updated by Coordinator',
        ]);
    }

    /**
     * Test: Regular member cannot update class schedule
     */
    public function test_member_cannot_update_class_schedule()
    {
        $schedule = ClassSchedule::factory()->create([
            'classroom_id' => $this->classroom->id,
        ]);

        $memberToken = JWTAuth::fromUser($this->member);

        $updateData = [
            'title' => 'Unauthorized Update',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $memberToken,
        ])->putJson("/api/classrooms/{$this->classroom->id}/schedules/{$schedule->id}", $updateData);

        $response->assertStatus(403);
    }

    /**
     * Test: Owner can delete class schedule
     */
    public function test_owner_can_delete_class_schedule()
    {
        $schedule = ClassSchedule::factory()->create([
            'classroom_id' => $this->classroom->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->deleteJson("/api/classrooms/{$this->classroom->id}/schedules/{$schedule->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Jadwal kelas berhasil dihapus'
            ]);

        $this->assertSoftDeleted('class_schedules', [
            'id' => $schedule->id,
        ]);
    }

    /**
     * Test: Coordinator can delete class schedule
     */
    public function test_coordinator_can_delete_class_schedule()
    {
        $coordinator = User::factory()->create();

        $schedule = ClassSchedule::factory()->create([
            'classroom_id' => $this->classroom->id,
            'coordinator_2' => $coordinator->id,
        ]);

        $coordinatorToken = JWTAuth::fromUser($coordinator);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $coordinatorToken,
        ])->deleteJson("/api/classrooms/{$this->classroom->id}/schedules/{$schedule->id}");

        $response->assertStatus(200);

        $this->assertSoftDeleted('class_schedules', [
            'id' => $schedule->id,
        ]);
    }

    /**
     * Test: Regular member cannot delete class schedule
     */
    public function test_member_cannot_delete_class_schedule()
    {
        $schedule = ClassSchedule::factory()->create([
            'classroom_id' => $this->classroom->id,
        ]);

        $memberToken = JWTAuth::fromUser($this->member);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $memberToken,
        ])->deleteJson("/api/classrooms/{$this->classroom->id}/schedules/{$schedule->id}");

        $response->assertStatus(403);
    }

    /**
     * Test: Cannot access schedule from different classroom
     */
    public function test_cannot_access_schedule_from_different_classroom()
    {
        $otherClassroom = Classroom::factory()->create();
        $schedule = ClassSchedule::factory()->create([
            'classroom_id' => $otherClassroom->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson("/api/classrooms/{$this->classroom->id}/schedules/{$schedule->id}");

        $response->assertStatus(404);
    }

    /**
     * Test: Schedules are ordered by start time
     */
    public function test_schedules_are_ordered_by_start_time()
    {
        ClassSchedule::factory()->create([
            'classroom_id' => $this->classroom->id,
            'title' => 'Third',
            'start_time' => '2025-11-20 14:00:00',
        ]);

        ClassSchedule::factory()->create([
            'classroom_id' => $this->classroom->id,
            'title' => 'First',
            'start_time' => '2025-11-20 08:00:00',
        ]);

        ClassSchedule::factory()->create([
            'classroom_id' => $this->classroom->id,
            'title' => 'Second',
            'start_time' => '2025-11-20 10:00:00',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson("/api/classrooms/{$this->classroom->id}/schedules");

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertEquals('First', $data[0]['title']);
        $this->assertEquals('Second', $data[1]['title']);
        $this->assertEquals('Third', $data[2]['title']);
    }

    /**
     * Test: Color validation accepts valid hex color
     */
    public function test_color_validation_accepts_valid_hex_color()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson("/api/classrooms/{$this->classroom->id}/schedules", [
            'title' => 'Test Schedule',
            'start_time' => '2025-11-20 08:00:00',
            'end_time' => '2025-11-20 10:00:00',
            'color' => '#FF5733',
        ]);

        $response->assertStatus(201);
    }

    /**
     * Test: Color validation rejects invalid hex color
     */
    public function test_color_validation_rejects_invalid_hex_color()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson("/api/classrooms/{$this->classroom->id}/schedules", [
            'title' => 'Test Schedule',
            'start_time' => '2025-11-20 08:00:00',
            'end_time' => '2025-11-20 10:00:00',
            'color' => 'invalid-color',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['color']);
    }

    /**
     * Test: Default color is applied when not provided
     */
    public function test_default_color_is_applied_when_not_provided()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson("/api/classrooms/{$this->classroom->id}/schedules", [
            'title' => 'Test Schedule',
            'start_time' => '2025-11-20 08:00:00',
            'end_time' => '2025-11-20 10:00:00',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'color' => '#5CD9C1'
                ]
            ]);
    }
}
