<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Classroom;
use App\Models\PersonalSchedule;
use App\Models\ClassSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class CombinedScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $otherUser;
    protected Classroom $classroomA;
    protected Classroom $classroomB;
    protected Classroom $otherClassroom;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        // Create users
        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();

        // Create classrooms
        $this->classroomA = Classroom::factory()->create([
            'owner_id' => $this->user->id,
            'name' => 'Classroom A',
        ]);
        $this->classroomA->users()->attach($this->user->id);

        $this->classroomB = Classroom::factory()->create([
            'owner_id' => $this->user->id,
            'name' => 'Classroom B',
        ]);
        $this->classroomB->users()->attach($this->user->id);

        // Create classroom that user is not a member of
        $this->otherClassroom = Classroom::factory()->create([
            'owner_id' => $this->otherUser->id,
            'name' => 'Other Classroom',
        ]);
        $this->otherClassroom->users()->attach($this->otherUser->id);

        // Generate JWT token
        $this->token = JWTAuth::fromUser($this->user);
    }

    protected function authHeaders(): array
    {
        return [
            'Authorization' => "Bearer {$this->token}",
            'Accept' => 'application/json',
        ];
    }

    /**
     * Test: User can get all combined schedules (personal + all classrooms)
     */
    public function test_user_can_get_all_combined_schedules(): void
    {
        // Create personal schedules
        PersonalSchedule::factory()->count(2)->create([
            'user_id' => $this->user->id,
        ]);

        // Create class schedules
        ClassSchedule::factory()->count(2)->create([
            'classroom_id' => $this->classroomA->id,
        ]);
        ClassSchedule::factory()->count(2)->create([
            'classroom_id' => $this->classroomB->id,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/schedules');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'type',
                        'title',
                        'start_time',
                        'end_time',
                        'location',
                        'color',
                        'source_id',
                        'source_name',
                    ]
                ],
                'meta' => [
                    'total',
                    'available_sources',
                    'current_filter',
                ]
            ]);

        // Should have 6 schedules total (2 personal + 2 from A + 2 from B)
        $response->assertJsonCount(6, 'data');
    }

    /**
     * Test: User can filter by personal schedules only
     */
    public function test_user_can_filter_by_personal_schedules_only(): void
    {
        // Create personal schedules
        PersonalSchedule::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        // Create class schedules (should not be included)
        ClassSchedule::factory()->count(2)->create([
            'classroom_id' => $this->classroomA->id,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/schedules?source=personal');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.current_filter', 'personal');

        // All schedules should be type 'personal'
        $data = $response->json('data');
        foreach ($data as $schedule) {
            $this->assertEquals('personal', $schedule['type']);
            $this->assertEquals('Personal Schedule', $schedule['source_name']);
            $this->assertNull($schedule['source_id']);
        }
    }

    /**
     * Test: User can filter by specific classroom
     */
    public function test_user_can_filter_by_specific_classroom(): void
    {
        // Create personal schedules (should not be included)
        PersonalSchedule::factory()->count(2)->create([
            'user_id' => $this->user->id,
        ]);

        // Create class schedules for classroom A
        ClassSchedule::factory()->count(3)->create([
            'classroom_id' => $this->classroomA->id,
        ]);

        // Create class schedules for classroom B (should not be included)
        ClassSchedule::factory()->count(2)->create([
            'classroom_id' => $this->classroomB->id,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/schedules?source=classroom:{$this->classroomA->id}");

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.current_filter', "classroom:{$this->classroomA->id}");

        // All schedules should be type 'class' and from classroom A
        $data = $response->json('data');
        foreach ($data as $schedule) {
            $this->assertEquals('class', $schedule['type']);
            $this->assertEquals($this->classroomA->id, $schedule['source_id']);
            $this->assertEquals('Classroom A', $schedule['source_name']);
        }
    }

    /**
     * Test: User cannot access schedules from classroom they are not a member of
     */
    public function test_user_cannot_access_schedules_from_non_member_classroom(): void
    {
        // Create schedules in other classroom
        ClassSchedule::factory()->count(2)->create([
            'classroom_id' => $this->otherClassroom->id,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/schedules?source=classroom:{$this->otherClassroom->id}");

        $response->assertNotFound()
            ->assertJson([
                'message' => 'Classroom not found or you do not have access',
            ]);
    }

    /**
     * Test: Unauthenticated user cannot access combined schedules
     */
    public function test_unauthenticated_user_cannot_access_combined_schedules(): void
    {
        $response = $this->getJson('/api/schedules');

        $response->assertStatus(401);
    }

    /**
     * Test: Response includes available sources for dropdown
     */
    public function test_response_includes_available_sources(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/schedules');

        $response->assertOk()
            ->assertJsonStructure([
                'meta' => [
                    'available_sources' => [
                        '*' => [
                            'id',
                            'type',
                            'name',
                            'description',
                        ]
                    ]
                ]
            ]);

        $availableSources = $response->json('meta.available_sources');

        // Should have 'all' option
        $allOption = collect($availableSources)->firstWhere('id', 'all');
        $this->assertNotNull($allOption);
        $this->assertEquals('All Schedules', $allOption['name']);

        // Should have 'personal' option
        $personalOption = collect($availableSources)->firstWhere('id', 'personal');
        $this->assertNotNull($personalOption);
        $this->assertEquals('Personal Schedule', $personalOption['name']);

        // Should have both classrooms
        $classroomAOption = collect($availableSources)->firstWhere('id', "classroom:{$this->classroomA->id}");
        $this->assertNotNull($classroomAOption);
        $this->assertEquals('Classroom A', $classroomAOption['name']);

        $classroomBOption = collect($availableSources)->firstWhere('id', "classroom:{$this->classroomB->id}");
        $this->assertNotNull($classroomBOption);
        $this->assertEquals('Classroom B', $classroomBOption['name']);

        // Should NOT have other classroom
        $otherClassroomOption = collect($availableSources)->firstWhere('id', "classroom:{$this->otherClassroom->id}");
        $this->assertNull($otherClassroomOption);
    }

    /**
     * Test: Schedules are sorted by start_time
     */
    public function test_schedules_are_sorted_by_start_time(): void
    {
        // Create schedules with different start times
        PersonalSchedule::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Third',
            'start_time' => '2025-11-20 14:00:00',
        ]);

        ClassSchedule::factory()->create([
            'classroom_id' => $this->classroomA->id,
            'title' => 'First',
            'start_time' => '2025-11-20 08:00:00',
        ]);

        PersonalSchedule::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Second',
            'start_time' => '2025-11-20 10:00:00',
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/schedules');

        $response->assertOk();

        $data = $response->json('data');
        $this->assertEquals('First', $data[0]['title']);
        $this->assertEquals('Second', $data[1]['title']);
        $this->assertEquals('Third', $data[2]['title']);
    }

    /**
     * Test: Personal schedules include correct fields
     */
    public function test_personal_schedules_include_correct_fields(): void
    {
        $personalSchedule = PersonalSchedule::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Test Personal',
            'location' => 'Library',
            'description' => 'Test description',
            'color' => '#10B981',
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/schedules?source=personal');

        $response->assertOk();

        $data = $response->json('data');
        $schedule = $data[0];

        $this->assertEquals('personal', $schedule['type']);
        $this->assertEquals('Test Personal', $schedule['title']);
        $this->assertEquals('Library', $schedule['location']);
        $this->assertEquals('Test description', $schedule['description']);
        $this->assertEquals('#10B981', $schedule['color']);
        $this->assertNull($schedule['source_id']);
        $this->assertEquals('Personal Schedule', $schedule['source_name']);
        $this->assertArrayNotHasKey('lecturer', $schedule);
        $this->assertArrayNotHasKey('coordinator_1', $schedule);
        $this->assertArrayNotHasKey('coordinator_2', $schedule);
    }

    /**
     * Test: Class schedules include correct fields
     */
    public function test_class_schedules_include_correct_fields(): void
    {
        $coordinator1 = User::factory()->create(['name' => 'Coordinator One']);
        $coordinator2 = User::factory()->create(['name' => 'Coordinator Two']);

        $classSchedule = ClassSchedule::factory()->create([
            'classroom_id' => $this->classroomA->id,
            'title' => 'Test Class',
            'location' => 'Room 301',
            'lecturer' => 'Dr. Smith',
            'description' => 'Class description',
            'color' => '#5CD9C1',
            'coordinator_1' => $coordinator1->id,
            'coordinator_2' => $coordinator2->id,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson("/api/schedules?source=classroom:{$this->classroomA->id}");

        $response->assertOk();

        $data = $response->json('data');
        $schedule = $data[0];

        $this->assertEquals('class', $schedule['type']);
        $this->assertEquals('Test Class', $schedule['title']);
        $this->assertEquals('Room 301', $schedule['location']);
        $this->assertEquals('Dr. Smith', $schedule['lecturer']);
        $this->assertEquals('Class description', $schedule['description']);
        $this->assertEquals('#5CD9C1', $schedule['color']);
        $this->assertEquals($this->classroomA->id, $schedule['source_id']);
        $this->assertEquals('Classroom A', $schedule['source_name']);
        $this->assertEquals($coordinator1->id, $schedule['coordinator_1']);
        $this->assertEquals($coordinator2->id, $schedule['coordinator_2']);
        $this->assertArrayHasKey('coordinator1', $schedule);
        $this->assertArrayHasKey('coordinator2', $schedule);
    }

    /**
     * Test: User who is member (not owner) can see classroom schedules
     */
    public function test_member_can_see_classroom_schedules(): void
    {
        $member = User::factory()->create();
        $classroom = Classroom::factory()->create([
            'owner_id' => $this->user->id,
        ]);
        $classroom->users()->attach($member->id);

        ClassSchedule::factory()->count(2)->create([
            'classroom_id' => $classroom->id,
        ]);

        $memberToken = JWTAuth::fromUser($member);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$memberToken}",
            'Accept' => 'application/json',
        ])->getJson("/api/schedules?source=classroom:{$classroom->id}");

        $response->assertOk()
            ->assertJsonCount(2, 'data');
    }

    /**
     * Test: User cannot see schedules from classroom they left
     */
    public function test_user_cannot_see_schedules_from_classroom_they_left(): void
    {
        $member = User::factory()->create();
        $classroom = Classroom::factory()->create([
            'owner_id' => $this->user->id,
        ]);
        $classroom->users()->attach($member->id);

        ClassSchedule::factory()->count(2)->create([
            'classroom_id' => $classroom->id,
        ]);

        // User leaves classroom
        $classroom->users()->detach($member->id);

        $memberToken = JWTAuth::fromUser($member);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$memberToken}",
            'Accept' => 'application/json',
        ])->getJson("/api/schedules?source=classroom:{$classroom->id}");

        $response->assertNotFound();
    }

    /**
     * Test: Empty result when no schedules exist
     */
    public function test_empty_result_when_no_schedules_exist(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/schedules');

        $response->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 0);
    }

    /**
     * Test: Invalid classroom ID in filter returns 404
     */
    public function test_invalid_classroom_id_in_filter_returns_404(): void
    {
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/schedules?source=classroom:99999');

        $response->assertNotFound();
    }

    /**
     * Test: Invalid source format is handled gracefully
     */
    public function test_invalid_source_format_is_handled_gracefully(): void
    {
        // Invalid format should be treated as "all"
        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/schedules?source=invalid_format');

        // Should return all schedules (or handle gracefully)
        $response->assertOk();
    }

    /**
     * Test: Meta includes correct total count
     */
    public function test_meta_includes_correct_total_count(): void
    {
        PersonalSchedule::factory()->count(3)->create([
            'user_id' => $this->user->id,
        ]);

        ClassSchedule::factory()->count(4)->create([
            'classroom_id' => $this->classroomA->id,
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/schedules');

        $response->assertOk()
            ->assertJsonPath('meta.total', 7);
    }
    /**
     * Test: User can filter schedules by date range
     */
    public function test_user_can_filter_schedules_by_date_range(): void
    {
        // Create schedules inside range
        PersonalSchedule::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Inside Range',
            'start_time' => '2025-11-20 10:00:00',
            'end_time' => '2025-11-20 11:00:00',
        ]);

        // Create schedules outside range (before)
        PersonalSchedule::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Before Range',
            'start_time' => '2025-11-19 10:00:00',
            'end_time' => '2025-11-19 11:00:00',
        ]);

        // Create schedules outside range (after)
        PersonalSchedule::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'After Range',
            'start_time' => '2025-11-21 10:00:00',
            'end_time' => '2025-11-21 11:00:00',
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/schedules?start_date=2025-11-20&end_date=2025-11-20');

        $response->assertOk()
            ->assertJsonCount(1, 'data');

        $this->assertEquals('Inside Range', $response->json('data.0.title'));
    }

    /**
     * Test: User can filter schedules from date onwards
     */
    public function test_user_can_filter_schedules_from_date_onwards(): void
    {
        // Create past schedule
        PersonalSchedule::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Past',
            'start_time' => '2025-11-19 10:00:00',
        ]);

        // Create future schedule
        PersonalSchedule::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Future',
            'start_time' => '2025-11-21 10:00:00',
        ]);

        $response = $this->withHeaders($this->authHeaders())
            ->getJson('/api/schedules?start_date=2025-11-20');

        $response->assertOk()
            ->assertJsonCount(1, 'data');

        $this->assertEquals('Future', $response->json('data.0.title'));
    }
}

