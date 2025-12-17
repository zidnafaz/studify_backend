<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Classroom;
use App\Models\ClassSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Services\NotificationService;
use Mockery\MockInterface;

class ClassroomTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $owner;
    protected $member;
    protected $nonMember;
    protected $token;
    protected $notificationServiceMock;

    /**
     * Setup test environment
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Mock Messaging to prevent Firebase connection attempts
        $this->mock(\Kreait\Firebase\Contract\Messaging::class, function (MockInterface $mock) {
            //
        });

        // Mock NotificationService
        $this->notificationServiceMock = $this->mock(NotificationService::class, function (MockInterface $mock) {
            $mock->shouldReceive('sendToUsers')->byDefault();
            $mock->shouldReceive('sendToUser')->byDefault();
        });

        // Create users
        $this->owner = User::factory()->create();
        $this->member = User::factory()->create();
        $this->nonMember = User::factory()->create();

        // Generate JWT token for owner
        $this->token = JWTAuth::fromUser($this->owner);
    }

    /**
     * Test: Authenticated user can list their classrooms
     */
    public function test_authenticated_user_can_list_their_classrooms()
    {
        // Create classrooms owned by owner
        $ownedClassroom = Classroom::factory()->create([
            'owner_id' => $this->owner->id,
        ]);
        $ownedClassroom->users()->attach($this->owner->id);

        // Create classroom where owner is member
        $memberClassroom = Classroom::factory()->create();
        $memberClassroom->users()->attach($this->owner->id);

        // Create classroom not related to owner
        Classroom::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/classrooms');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'owner_id',
                        'name',
                        'description',
                        'unique_code',
                        'owner' => ['id', 'name', 'email'],
                        'users' => [
                            '*' => ['id', 'name', 'email']
                        ],
                        'users_count',
                    ]
                ]
            ])
            ->assertJsonCount(2, 'data'); // Only owned and member classrooms
    }

    /**
     * Test: Unauthenticated user cannot list classrooms
     */
    public function test_unauthenticated_user_cannot_list_classrooms()
    {
        $response = $this->getJson('/api/classrooms');

        $response->assertStatus(401);
    }

    /**
     * Test: Owner can create classroom
     */
    public function test_owner_can_create_classroom()
    {
        $classroomData = [
            'name' => 'Pemrograman Web',
            'description' => 'Kelas untuk belajar pemrograman web',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/classrooms', $classroomData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'owner_id',
                    'name',
                    'description',
                    'unique_code',
                    'owner' => ['id', 'name', 'email'],
                    'users' => [
                        '*' => ['id', 'name', 'email']
                    ],
                    'users_count',
                ]
            ])
            ->assertJson([
                'message' => 'Classroom created successfully',
                'data' => [
                    'name' => 'Pemrograman Web',
                    'description' => 'Kelas untuk belajar pemrograman web',
                    'owner_id' => $this->owner->id,
                ]
            ]);

        // Verify database
        $this->assertDatabaseHas('classrooms', [
            'owner_id' => $this->owner->id,
            'name' => 'Pemrograman Web',
            'description' => 'Kelas untuk belajar pemrograman web',
        ]);

        // Verify unique_code is generated
        $classroom = Classroom::where('name', 'Pemrograman Web')->first();
        $this->assertNotNull($classroom->unique_code);
        $this->assertEquals(8, strlen($classroom->unique_code));

        // Verify owner is automatically added to classroom_user
        $this->assertDatabaseHas('classroom_user', [
            'classroom_id' => $classroom->id,
            'user_id' => $this->owner->id,
        ]);
    }

    /**
     * Test: Create classroom validation fails with invalid data
     */
    public function test_create_classroom_validation_fails_with_invalid_data()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/classrooms', [
            'name' => '', // Required field is empty
            'description' => str_repeat('a', 1001), // Exceeds max length
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors'
            ])
            ->assertJsonValidationErrors(['name', 'description']);
    }

    /**
     * Test: Create classroom validation - name is required
     */
    public function test_create_classroom_name_is_required()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/classrooms', [
            'description' => 'Some description',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    /**
     * Test: Create classroom validation - description is optional
     */
    public function test_create_classroom_description_is_optional()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/classrooms', [
            'name' => 'Test Classroom',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'name' => 'Test Classroom',
                    'description' => null,
                ]
            ]);
    }

    /**
     * Test: Unique code is generated and unique
     */
    public function test_unique_code_is_generated_and_unique()
    {
        // Create multiple classrooms
        $classroom1 = Classroom::factory()->create(['owner_id' => $this->owner->id]);
        $classroom1->users()->attach($this->owner->id);

        $classroomData = [
            'name' => 'Another Classroom',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('/api/classrooms', $classroomData);

        $response->assertStatus(201);

        $classroom2 = Classroom::where('name', 'Another Classroom')->first();

        // Verify codes are different
        $this->assertNotEquals($classroom1->unique_code, $classroom2->unique_code);
    }

    /**
     * Test: User can join classroom with valid unique code
     */
    public function test_user_can_join_classroom_with_valid_unique_code()
    {
        $classroom = Classroom::factory()->create([
            'unique_code' => strtoupper(Str::random(8)),
        ]);
        $classroom->users()->attach($classroom->owner_id);

        $memberToken = JWTAuth::fromUser($this->member);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $memberToken,
        ])->postJson('/api/classrooms/join', [
            'unique_code' => $classroom->unique_code,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'name',
                    'unique_code',
                    'owner' => ['id', 'name', 'email'],
                    'users' => [
                        '*' => ['id', 'name', 'email']
                    ],
                    'users_count',
                ]
            ])
            ->assertJson([
                'message' => 'Successfully joined classroom',
            ]);

        // Verify user is added to classroom_user
        $this->assertDatabaseHas('classroom_user', [
            'classroom_id' => $classroom->id,
            'user_id' => $this->member->id,
        ]);
    }

    /**
     * Test: Join classroom validation fails with invalid unique code
     */
    public function test_join_classroom_validation_fails_with_invalid_unique_code()
    {
        $memberToken = JWTAuth::fromUser($this->member);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $memberToken,
        ])->postJson('/api/classrooms/join', [
            'unique_code' => 'INVALID', // Wrong size
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['unique_code']);
    }

    /**
     * Test: Join classroom fails with non-existent unique code
     */
    public function test_join_classroom_fails_with_non_existent_unique_code()
    {
        $memberToken = JWTAuth::fromUser($this->member);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $memberToken,
        ])->postJson('/api/classrooms/join', [
            'unique_code' => 'ABCD1234',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Classroom not found with this code',
            ]);
    }

    /**
     * Test: User cannot join classroom if already a member
     */
    public function test_user_cannot_join_classroom_if_already_member()
    {
        $classroom = Classroom::factory()->create([
            'unique_code' => strtoupper(Str::random(8)),
        ]);
        $classroom->users()->attach($classroom->owner_id);
        $classroom->users()->attach($this->member->id);

        $memberToken = JWTAuth::fromUser($this->member);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $memberToken,
        ])->postJson('/api/classrooms/join', [
            'unique_code' => $classroom->unique_code,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'You are already a member of this classroom',
            ]);
    }

    /**
     * Test: Owner can view classroom detail
     */
    public function test_owner_can_view_classroom_detail()
    {
        $classroom = Classroom::factory()->create([
            'owner_id' => $this->owner->id,
        ]);
        $classroom->users()->attach($this->owner->id);
        $classroom->users()->attach($this->member->id);

        // Create some schedules
        ClassSchedule::factory()->count(2)->create([
            'classroom_id' => $classroom->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson("/api/classrooms/{$classroom->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'owner_id',
                    'name',
                    'description',
                    'unique_code',
                    'owner' => ['id', 'name', 'email'],
                    'users' => [
                        '*' => [
                            'id',
                            'name',
                            'email',
                            'is_coordinator',
                            'coordinator_schedules',
                        ]
                    ],
                    'users_count',
                    'class_schedules' => [
                        '*' => [
                            'id',
                            'classroom_id',
                            'title',
                            'start_time',
                            'end_time',
                            'color',
                        ]
                    ],
                ]
            ])
            ->assertJson([
                'data' => [
                    'id' => $classroom->id,
                    'name' => $classroom->name,
                ]
            ]);
    }

    /**
     * Test: Member can view classroom detail
     */
    public function test_member_can_view_classroom_detail()
    {
        $classroom = Classroom::factory()->create();
        $classroom->users()->attach($classroom->owner_id);
        $classroom->users()->attach($this->member->id);

        $memberToken = JWTAuth::fromUser($this->member);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $memberToken,
        ])->getJson("/api/classrooms/{$classroom->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $classroom->id,
                ]
            ]);
    }

    /**
     * Test: Non-member cannot view classroom detail
     */
    public function test_non_member_cannot_view_classroom_detail()
    {
        $classroom = Classroom::factory()->create();
        $classroom->users()->attach($classroom->owner_id);

        $nonMemberToken = JWTAuth::fromUser($this->nonMember);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $nonMemberToken,
        ])->getJson("/api/classrooms/{$classroom->id}");

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Unauthorized access',
            ]);
    }

    /**
     * Test: View classroom detail returns 404 for non-existent classroom
     */
    public function test_view_classroom_detail_returns_404_for_non_existent_classroom()
    {
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/classrooms/99999');

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Classroom not found',
            ]);
    }

    /**
     * Test: Owner can update classroom
     */
    public function test_owner_can_update_classroom()
    {
        $classroom = Classroom::factory()->create([
            'owner_id' => $this->owner->id,
            'name' => 'Old Name',
            'description' => 'Old Description',
        ]);
        $classroom->users()->attach($this->owner->id);

        $updateData = [
            'name' => 'Updated Name',
            'description' => 'Updated Description',
        ];

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson("/api/classrooms/{$classroom->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'name',
                    'description',
                ]
            ])
            ->assertJson([
                'message' => 'Classroom updated successfully',
                'data' => [
                    'name' => 'Updated Name',
                    'description' => 'Updated Description',
                ]
            ]);

        $this->assertDatabaseHas('classrooms', [
            'id' => $classroom->id,
            'name' => 'Updated Name',
            'description' => 'Updated Description',
        ]);
    }

    /**
     * Test: Member cannot update classroom
     */
    public function test_member_cannot_update_classroom()
    {
        $classroom = Classroom::factory()->create();
        $classroom->users()->attach($classroom->owner_id);
        $classroom->users()->attach($this->member->id);

        $memberToken = JWTAuth::fromUser($this->member);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $memberToken,
        ])->putJson("/api/classrooms/{$classroom->id}", [
            'name' => 'Unauthorized Update',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Only classroom owner can update',
            ]);
    }

    /**
     * Test: Update classroom validation fails with invalid data
     */
    public function test_update_classroom_validation_fails_with_invalid_data()
    {
        $classroom = Classroom::factory()->create([
            'owner_id' => $this->owner->id,
        ]);
        $classroom->users()->attach($this->owner->id);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->putJson("/api/classrooms/{$classroom->id}", [
            'name' => '', // Empty name
            'description' => str_repeat('a', 1001), // Exceeds max
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'description']);
    }

    /**
     * Test: Owner can delete classroom
     */
    public function test_owner_can_delete_classroom()
    {
        $classroom = Classroom::factory()->create([
            'owner_id' => $this->owner->id,
        ]);
        $classroom->users()->attach($this->owner->id);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->deleteJson("/api/classrooms/{$classroom->id}");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Classroom deleted successfully',
            ]);

        $this->assertSoftDeleted('classrooms', [
            'id' => $classroom->id,
        ]);
    }

    /**
     * Test: Member cannot delete classroom
     */
    public function test_member_cannot_delete_classroom()
    {
        $classroom = Classroom::factory()->create();
        $classroom->users()->attach($classroom->owner_id);
        $classroom->users()->attach($this->member->id);

        $memberToken = JWTAuth::fromUser($this->member);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $memberToken,
        ])->deleteJson("/api/classrooms/{$classroom->id}");

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Only classroom owner can delete',
            ]);
    }

    /**
     * Test: Member can leave classroom
     */
    public function test_member_can_leave_classroom()
    {
        $classroom = Classroom::factory()->create();
        $classroom->users()->attach($classroom->owner_id);
        $classroom->users()->attach($this->member->id);

        $memberToken = JWTAuth::fromUser($this->member);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $memberToken,
        ])->postJson("/api/classrooms/{$classroom->id}/leave");

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Successfully left classroom',
            ]);

        // Verify user is removed from classroom_user
        $this->assertDatabaseMissing('classroom_user', [
            'classroom_id' => $classroom->id,
            'user_id' => $this->member->id,
        ]);
    }

    /**
     * Test: Owner cannot leave their own classroom
     */
    public function test_owner_cannot_leave_their_own_classroom()
    {
        $classroom = Classroom::factory()->create([
            'owner_id' => $this->owner->id,
        ]);
        $classroom->users()->attach($this->owner->id);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson("/api/classrooms/{$classroom->id}/leave");

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Classroom owner cannot leave. Please transfer ownership first.',
            ]);
    }

    /**
     * Test: User cannot leave classroom if not a member
     */
    public function test_user_cannot_leave_classroom_if_not_member()
    {
        $classroom = Classroom::factory()->create();
        $classroom->users()->attach($classroom->owner_id);

        $nonMemberToken = JWTAuth::fromUser($this->nonMember);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $nonMemberToken,
        ])->postJson("/api/classrooms/{$classroom->id}/leave");

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'You are not a member of this classroom',
            ]);
    }

    /**
     * Test: Owner can remove member from classroom
     */
    public function test_owner_can_remove_member_from_classroom()
    {
        $classroom = Classroom::factory()->create([
            'owner_id' => $this->owner->id,
        ]);
        $classroom->users()->attach($this->owner->id);
        $classroom->users()->attach($this->member->id);

        // Create schedule where member is coordinator
        $schedule = ClassSchedule::factory()->create([
            'classroom_id' => $classroom->id,
            'coordinator_1' => $this->member->id,
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson("/api/classrooms/{$classroom->id}/remove-member", [
            'user_id' => $this->member->id,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Member removed successfully',
            ]);

        // Verify user is removed from classroom_user
        $this->assertDatabaseMissing('classroom_user', [
            'classroom_id' => $classroom->id,
            'user_id' => $this->member->id,
        ]);

        // Verify coordinator is set back to owner
        $schedule->refresh();
        $this->assertEquals($this->owner->id, $schedule->coordinator_1);
    }

    /**
     * Test: Member cannot remove other members
     */
    public function test_member_cannot_remove_other_members()
    {
        $classroom = Classroom::factory()->create();
        $classroom->users()->attach($classroom->owner_id);
        $classroom->users()->attach($this->member->id);
        $classroom->users()->attach($this->nonMember->id);

        $memberToken = JWTAuth::fromUser($this->member);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $memberToken,
        ])->postJson("/api/classrooms/{$classroom->id}/remove-member", [
            'user_id' => $this->nonMember->id,
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Only classroom owner can remove members',
            ]);
    }

    /**
     * Test: Owner cannot remove themselves
     */
    public function test_owner_cannot_remove_themselves()
    {
        $classroom = Classroom::factory()->create([
            'owner_id' => $this->owner->id,
        ]);
        $classroom->users()->attach($this->owner->id);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson("/api/classrooms/{$classroom->id}/remove-member", [
            'user_id' => $this->owner->id,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Cannot remove classroom owner',
            ]);
    }

    /**
     * Test: Remove member validation fails with invalid user_id
     */
    public function test_remove_member_validation_fails_with_invalid_user_id()
    {
        $classroom = Classroom::factory()->create([
            'owner_id' => $this->owner->id,
        ]);
        $classroom->users()->attach($this->owner->id);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson("/api/classrooms/{$classroom->id}/remove-member", [
            'user_id' => 'invalid',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['user_id']);
    }

    /**
     * Test: Owner can transfer ownership
     */
    public function test_owner_can_transfer_ownership()
    {
        $classroom = Classroom::factory()->create([
            'owner_id' => $this->owner->id,
        ]);
        $classroom->users()->attach($this->owner->id);
        $classroom->users()->attach($this->member->id);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson("/api/classrooms/{$classroom->id}/transfer-ownership", [
            'new_owner_id' => $this->member->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'message',
                'data' => [
                    'id',
                    'owner_id',
                    'owner' => ['id', 'name', 'email'],
                ]
            ])
            ->assertJson([
                'message' => 'Ownership transferred successfully',
                'data' => [
                    'owner_id' => $this->member->id,
                ]
            ]);

        $this->assertDatabaseHas('classrooms', [
            'id' => $classroom->id,
            'owner_id' => $this->member->id,
        ]);
    }

    /**
     * Test: Member cannot transfer ownership
     */
    public function test_member_cannot_transfer_ownership()
    {
        $classroom = Classroom::factory()->create();
        $classroom->users()->attach($classroom->owner_id);
        $classroom->users()->attach($this->member->id);

        $memberToken = JWTAuth::fromUser($this->member);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $memberToken,
        ])->postJson("/api/classrooms/{$classroom->id}/transfer-ownership", [
            'new_owner_id' => $this->member->id,
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Only classroom owner can transfer ownership',
            ]);
    }

    /**
     * Test: Transfer ownership fails if new owner is not a member
     */
    public function test_transfer_ownership_fails_if_new_owner_is_not_member()
    {
        $classroom = Classroom::factory()->create([
            'owner_id' => $this->owner->id,
        ]);
        $classroom->users()->attach($this->owner->id);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson("/api/classrooms/{$classroom->id}/transfer-ownership", [
            'new_owner_id' => $this->nonMember->id,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'New owner must be a member of the classroom',
            ]);
    }

    /**
     * Test: Transfer ownership validation fails with invalid new_owner_id
     */
    public function test_transfer_ownership_validation_fails_with_invalid_new_owner_id()
    {
        $classroom = Classroom::factory()->create([
            'owner_id' => $this->owner->id,
        ]);
        $classroom->users()->attach($this->owner->id);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson("/api/classrooms/{$classroom->id}/transfer-ownership", [
            'new_owner_id' => 'invalid',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['new_owner_id']);
    }

    /**
     * Test: Classroom detail shows coordinator information for users
     */
    public function test_classroom_detail_shows_coordinator_information()
    {
        $classroom = Classroom::factory()->create([
            'owner_id' => $this->owner->id,
        ]);
        $classroom->users()->attach($this->owner->id);
        $classroom->users()->attach($this->member->id);

        // Create schedule where member is coordinator
        ClassSchedule::factory()->create([
            'classroom_id' => $classroom->id,
            'coordinator_1' => $this->member->id,
            'title' => 'Pemrograman Web',
            'color' => '#5CD9C1',
        ]);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson("/api/classrooms/{$classroom->id}");

        $response->assertStatus(200);

        $data = $response->json('data');
        $memberData = collect($data['users'])->firstWhere('id', $this->member->id);

        $this->assertTrue($memberData['is_coordinator']);
        $this->assertNotEmpty($memberData['coordinator_schedules']);
        $this->assertEquals('Pemrograman Web', $memberData['coordinator_schedules'][0]['title']);
    }

    /**
     * Test: Classroom list is ordered by created_at desc
     */
    public function test_classroom_list_is_ordered_by_created_at_desc()
    {
        $classroom1 = Classroom::factory()->create([
            'owner_id' => $this->owner->id,
            'created_at' => now()->subDays(2),
        ]);
        $classroom1->users()->attach($this->owner->id);

        $classroom2 = Classroom::factory()->create([
            'owner_id' => $this->owner->id,
            'created_at' => now()->subDay(),
        ]);
        $classroom2->users()->attach($this->owner->id);

        $classroom3 = Classroom::factory()->create([
            'owner_id' => $this->owner->id,
            'created_at' => now(),
        ]);
        $classroom3->users()->attach($this->owner->id);

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->getJson('/api/classrooms');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertEquals($classroom3->id, $data[0]['id']);
        $this->assertEquals($classroom2->id, $data[1]['id']);
        $this->assertEquals($classroom1->id, $data[2]['id']);
    }
}

