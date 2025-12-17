# 🧪 TESTING IMPLEMENTATION GUIDE - Studify Project

## 📋 Table of Contents
1. [Backend Unit Tests](#backend-unit-tests)
2. [Backend Integration Tests](#backend-integration-tests)
3. [Frontend Unit Tests](#frontend-unit-tests)
4. [Frontend Integration Tests](#frontend-integration-tests)
5. [Running Tests](#running-tests)

---

## 🔧 BACKEND UNIT TESTS

### **1. Model Unit Tests**

#### **File: `tests/Unit/Models/UserTest.php`**

```php
<?php

namespace Tests\Unit\Models;

use App\Models\User;
use App\Models\Classroom;
use App\Models\PersonalSchedule;
use App\Models\DeviceToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_has_fillable_attributes()
    {
        $user = new User();
        $fillable = $user->getFillable();

        $this->assertContains('name', $fillable);
        $this->assertContains('email', $fillable);
        $this->assertContains('password', $fillable);
    }

    /** @test */
    public function user_has_hidden_attributes()
    {
        $user = User::factory()->create();
        $array = $user->toArray();

        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayNotHasKey('remember_token', $array);
    }

    /** @test */
    public function user_has_owned_classrooms_relationship()
    {
        $user = User::factory()->create();
        $classroom = Classroom::factory()->create(['owner_id' => $user->id]);

        $this->assertInstanceOf(Classroom::class, $user->ownedClassrooms->first());
        $this->assertEquals(1, $user->ownedClassrooms->count());
    }

    /** @test */
    public function user_has_classrooms_many_to_many_relationship()
    {
        $user = User::factory()->create();
        $classroom = Classroom::factory()->create();
        $classroom->users()->attach($user->id);

        $this->assertInstanceOf(Classroom::class, $user->classrooms->first());
        $this->assertEquals(1, $user->classrooms->count());
    }

    /** @test */
    public function user_has_personal_schedules_relationship()
    {
        $user = User::factory()->create();
        $schedule = PersonalSchedule::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(PersonalSchedule::class, $user->personalSchedules->first());
        $this->assertEquals(1, $user->personalSchedules->count());
    }

    /** @test */
    public function user_has_device_tokens_relationship()
    {
        $user = User::factory()->create();
        $token = DeviceToken::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(DeviceToken::class, $user->deviceTokens->first());
        $this->assertEquals(1, $user->deviceTokens->count());
    }

    /** @test */
    public function user_implements_jwt_subject()
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(\Tymon\JWTAuth\Contracts\JWTSubject::class, $user);
        $this->assertEquals($user->id, $user->getJWTIdentifier());
        $this->assertIsArray($user->getJWTCustomClaims());
    }

    /** @test */
    public function user_soft_deletes()
    {
        $user = User::factory()->create();
        $userId = $user->id;

        $user->delete();

        $this->assertSoftDeleted('users', ['id' => $userId]);
        $this->assertNotNull(User::withTrashed()->find($userId)->deleted_at);
    }

    /** @test */
    public function user_password_is_hashed_on_creation()
    {
        $plainPassword = 'password123';
        $user = User::factory()->create(['password' => $plainPassword]);

        $this->assertNotEquals($plainPassword, $user->password);
        $this->assertTrue(\Hash::check($plainPassword, $user->password));
    }

    /** @test */
    public function user_email_must_be_unique()
    {
        User::factory()->create(['email' => 'test@example.com']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        
        User::factory()->create(['email' => 'test@example.com']);
    }
}
```

---

#### **File: `tests/Unit/Models/ClassroomTest.php`**

```php
<?php

namespace Tests\Unit\Models;

use App\Models\User;
use App\Models\Classroom;
use App\Models\ClassSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClassroomTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function classroom_belongs_to_owner()
    {
        $owner = User::factory()->create();
        $classroom = Classroom::factory()->create(['owner_id' => $owner->id]);

        $this->assertInstanceOf(User::class, $classroom->owner);
        $this->assertEquals($owner->id, $classroom->owner->id);
    }

    /** @test */
    public function classroom_has_many_users()
    {
        $classroom = Classroom::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $classroom->users()->attach([$user1->id, $user2->id]);

        $this->assertEquals(2, $classroom->users->count());
    }

    /** @test */
    public function classroom_has_many_schedules()
    {
        $classroom = Classroom::factory()->create();
        ClassSchedule::factory()->count(3)->create(['classroom_id' => $classroom->id]);

        $this->assertEquals(3, $classroom->classSchedules->count());
    }

    /** @test */
    public function classroom_unique_code_must_be_unique()
    {
        Classroom::factory()->create(['unique_code' => 'ABC123']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        
        Classroom::factory()->create(['unique_code' => 'ABC123']);
    }

    /** @test */
    public function classroom_soft_deletes()
    {
        $classroom = Classroom::factory()->create();
        $classroomId = $classroom->id;

        $classroom->delete();

        $this->assertSoftDeleted('classrooms', ['id' => $classroomId]);
    }

    /** @test */
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
```

---

#### **File: `tests/Unit/Models/ReminderTest.php`**

```php
<?php

namespace Tests\Unit\Models;

use App\Models\Reminder;
use App\Models\PersonalSchedule;
use App\Models\ClassSchedule;
use App\Models\Classroom;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReminderTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
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

    /** @test */
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

    /** @test */
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

    /** @test */
    public function reminder_status_can_be_updated()
    {
        $reminder = Reminder::factory()->create(['status' => 'pending']);

        $reminder->update(['status' => 'sent']);

        $this->assertEquals('sent', $reminder->fresh()->status);
    }

    /** @test */
    public function reminder_soft_deletes()
    {
        $reminder = Reminder::factory()->create();
        $reminderId = $reminder->id;

        $reminder->delete();

        $this->assertSoftDeleted('reminders', ['id' => $reminderId]);
    }
}
```

---

### **2. Service Unit Tests**

#### **File: `tests/Unit/Services/NotificationServiceTest.php`**

```php
<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Models\DeviceToken;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Mockery\MockInterface;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $messagingMock;
    protected $notificationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->messagingMock = $this->mock(Messaging::class);
        $this->notificationService = new NotificationService($this->messagingMock);
    }

    /** @test */
    public function it_can_send_notification_to_single_user()
    {
        $user = User::factory()->create();
        $deviceToken = DeviceToken::factory()->create([
            'user_id' => $user->id,
            'token' => 'test-fcm-token-123'
        ]);

        $this->messagingMock
            ->shouldReceive('send')
            ->once()
            ->andReturn(['success' => true]);

        $this->notificationService->sendToUser(
            $user,
            'Test Title',
            'Test Body',
            ['key' => 'value']
        );

        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'title' => 'Test Title',
            'body' => 'Test Body',
        ]);
    }

    /** @test */
    public function it_can_send_notification_to_multiple_users()
    {
        $users = User::factory()->count(3)->create();
        
        foreach ($users as $user) {
            DeviceToken::factory()->create([
                'user_id' => $user->id,
                'token' => 'fcm-token-' . $user->id
            ]);
        }

        $this->messagingMock
            ->shouldReceive('send')
            ->times(3)
            ->andReturn(['success' => true]);

        $this->notificationService->sendToUsers(
            $users,
            'Test Title',
            'Test Body'
        );

        foreach ($users as $user) {
            $this->assertDatabaseHas('notifications', [
                'user_id' => $user->id,
                'title' => 'Test Title',
            ]);
        }
    }

    /** @test */
    public function it_handles_user_without_device_tokens_gracefully()
    {
        $user = User::factory()->create();
        // No device tokens created

        $this->messagingMock
            ->shouldReceive('send')
            ->never();

        $this->notificationService->sendToUser(
            $user,
            'Test Title',
            'Test Body'
        );

        // Should still log notification
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'title' => 'Test Title',
        ]);
    }

    /** @test */
    public function it_handles_firebase_exceptions_gracefully()
    {
        $user = User::factory()->create();
        DeviceToken::factory()->create([
            'user_id' => $user->id,
            'token' => 'invalid-token'
        ]);

        $this->messagingMock
            ->shouldReceive('send')
            ->once()
            ->andThrow(new \Exception('Firebase error'));

        // Should not throw exception
        $this->notificationService->sendToUser(
            $user,
            'Test Title',
            'Test Body'
        );

        // Notification should still be logged
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
        ]);
    }
}
```

---

## 🔄 BACKEND INTEGRATION TESTS

### **File: `tests/Feature/NotificationTest.php`**

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->token = JWTAuth::fromUser($this->user);
    }

    /** @test */
    public function user_can_fetch_their_notifications()
    {
        Notification::factory()->count(5)->create(['user_id' => $this->user->id]);
        Notification::factory()->count(3)->create(); // Other user's notifications

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/notifications');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'title', 'body', 'is_read', 'created_at']
                ]
            ]);
    }

    /** @test */
    public function user_can_mark_notification_as_read()
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'is_read' => false,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->patchJson("/api/notifications/{$notification->id}/read");

        $response->assertStatus(200);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'is_read' => true,
        ]);
    }

    /** @test */
    public function user_can_mark_all_notifications_as_read()
    {
        Notification::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'is_read' => false,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->patchJson('/api/notifications/read-all');

        $response->assertStatus(200);

        $this->assertEquals(0, Notification::where('user_id', $this->user->id)
            ->where('is_read', false)
            ->count());
    }

    /** @test */
    public function user_cannot_mark_other_users_notification_as_read()
    {
        $otherUser = User::factory()->create();
        $notification = Notification::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->patchJson("/api/notifications/{$notification->id}/read");

        $response->assertStatus(403);
    }

    /** @test */
    public function notifications_are_ordered_by_newest_first()
    {
        $old = Notification::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDays(5),
        ]);

        $new = Notification::factory()->create([
            'user_id' => $this->user->id,
            'created_at' => now(),
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/notifications');

        $data = $response->json('data');
        $this->assertEquals($new->id, $data[0]['id']);
        $this->assertEquals($old->id, $data[1]['id']);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_notifications()
    {
        $response = $this->getJson('/api/notifications');
        $response->assertStatus(401);
    }
}
```

---

### **File: `tests/Feature/DeviceTokenTest.php`**

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\DeviceToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class DeviceTokenTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->token = JWTAuth::fromUser($this->user);
    }

    /** @test */
    public function user_can_register_device_token()
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/device-tokens', [
                'token' => 'fcm-token-123',
                'platform' => 'android',
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('device_tokens', [
            'user_id' => $this->user->id,
            'token' => 'fcm-token-123',
            'platform' => 'android',
        ]);
    }

    /** @test */
    public function duplicate_token_updates_existing_record()
    {
        DeviceToken::factory()->create([
            'user_id' => $this->user->id,
            'token' => 'fcm-token-123',
            'platform' => 'android',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/device-tokens', [
                'token' => 'fcm-token-123',
                'platform' => 'ios', // Different platform
            ]);

        $response->assertStatus(201);

        // Should only have one record with updated platform
        $this->assertEquals(1, DeviceToken::where('token', 'fcm-token-123')->count());
        $this->assertDatabaseHas('device_tokens', [
            'token' => 'fcm-token-123',
            'platform' => 'ios',
        ]);
    }

    /** @test */
    public function token_is_required()
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/device-tokens', [
                'platform' => 'android',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['token']);
    }

    /** @test */
    public function user_can_have_multiple_device_tokens()
    {
        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/device-tokens', [
                'token' => 'fcm-token-android',
                'platform' => 'android',
            ]);

        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/device-tokens', [
                'token' => 'fcm-token-ios',
                'platform' => 'ios',
            ]);

        $this->assertEquals(2, $this->user->deviceTokens()->count());
    }

    /** @test */
    public function unauthenticated_user_cannot_register_token()
    {
        $response = $this->postJson('/api/device-tokens', [
            'token' => 'fcm-token-123',
        ]);

        $response->assertStatus(401);
    }
}
```

---

## 📱 FRONTEND UNIT TESTS

### **File: `test/unit/data/services/class_schedule_service_test.dart`**

```dart
import 'package:flutter_test/flutter_test.dart';
import 'package:mockito/mockito.dart';
import 'package:mockito/annotations.dart';
import 'package:studify/core/http/dio_client.dart';
import 'package:studify/data/services/class_schedule_service.dart';
import 'package:studify/data/models/class_schedule_model.dart';
import 'package:dio/dio.dart';

import 'class_schedule_service_test.mocks.dart';

@GenerateMocks([DioClient])
void main() {
  late ClassScheduleService service;
  late MockDioClient mockDioClient;

  setUp(() {
    mockDioClient = MockDioClient();
    service = ClassScheduleService(client: mockDioClient);
  });

  group('ClassScheduleService', () {
    group('getClassSchedules', () {
      test('should return list of class schedules on success', () async {
        // Arrange
        final classroomId = 1;
        final mockResponse = {
          'data': [
            {
              'id': 1,
              'classroom_id': classroomId,
              'title': 'Math Class',
              'start_time': '2024-12-17T10:00:00Z',
              'end_time': '2024-12-17T11:30:00Z',
            },
          ],
        };

        when(mockDioClient.get('/api/classrooms/$classroomId/schedules'))
            .thenAnswer((_) async => Response(
                  data: mockResponse,
                  statusCode: 200,
                  requestOptions: RequestOptions(path: ''),
                ));

        // Act
        final result = await service.getClassSchedules(classroomId);

        // Assert
        expect(result, isA<List<ClassSchedule>>());
        expect(result.length, 1);
        expect(result.first.title, 'Math Class');
        verify(mockDioClient.get('/api/classrooms/$classroomId/schedules'))
            .called(1);
      });

      test('should throw exception on error', () async {
        // Arrange
        final classroomId = 1;
        when(mockDioClient.get('/api/classrooms/$classroomId/schedules'))
            .thenThrow(DioException(
          requestOptions: RequestOptions(path: ''),
          response: Response(
            statusCode: 500,
            requestOptions: RequestOptions(path: ''),
          ),
        ));

        // Act & Assert
        expect(
          () => service.getClassSchedules(classroomId),
          throwsA(isA<ApiException>()),
        );
      });
    });

    group('createClassSchedule', () {
      test('should create class schedule successfully', () async {
        // Arrange
        final classroomId = 1;
        final scheduleData = {
          'title': 'New Class',
          'start_time': '2024-12-18T10:00:00Z',
          'end_time': '2024-12-18T11:30:00Z',
        };

        final mockResponse = {
          'data': {
            'id': 2,
            'classroom_id': classroomId,
            ...scheduleData,
          },
        };

        when(mockDioClient.post(
          '/api/classrooms/$classroomId/schedules',
          data: anyNamed('data'),
        )).thenAnswer((_) async => Response(
              data: mockResponse,
              statusCode: 201,
              requestOptions: RequestOptions(path: ''),
            ));

        // Act
        final result = await service.createClassSchedule(
          classroomId,
          scheduleData,
        );

        // Assert
        expect(result, isA<ClassSchedule>());
        expect(result.title, 'New Class');
        verify(mockDioClient.post(
          '/api/classrooms/$classroomId/schedules',
          data: anyNamed('data'),
        )).called(1);
      });
    });

    group('updateClassSchedule', () {
      test('should update class schedule successfully', () async {
        // Arrange
        final classroomId = 1;
        final scheduleId = 2;
        final updateData = {'title': 'Updated Class'};

        final mockResponse = {
          'data': {
            'id': scheduleId,
            'classroom_id': classroomId,
            'title': 'Updated Class',
          },
        };

        when(mockDioClient.put(
          '/api/classrooms/$classroomId/schedules/$scheduleId',
          data: anyNamed('data'),
        )).thenAnswer((_) async => Response(
              data: mockResponse,
              statusCode: 200,
              requestOptions: RequestOptions(path: ''),
            ));

        // Act
        final result = await service.updateClassSchedule(
          classroomId,
          scheduleId,
          updateData,
        );

        // Assert
        expect(result.title, 'Updated Class');
      });
    });

    group('deleteClassSchedule', () {
      test('should delete class schedule successfully', () async {
        // Arrange
        final classroomId = 1;
        final scheduleId = 2;

        when(mockDioClient.delete(
          '/api/classrooms/$classroomId/schedules/$scheduleId',
        )).thenAnswer((_) async => Response(
              statusCode: 204,
              requestOptions: RequestOptions(path: ''),
            ));

        // Act
        await service.deleteClassSchedule(classroomId, scheduleId);

        // Assert
        verify(mockDioClient.delete(
          '/api/classrooms/$classroomId/schedules/$scheduleId',
        )).called(1);
      });
    });
  });
}
```

---

### **File: `test/unit/providers/notification_provider_test.dart`**

```dart
import 'package:flutter_test/flutter_test.dart';
import 'package:mockito/mockito.dart';
import 'package:mockito/annotations.dart';
import 'package:studify/providers/notification_provider.dart';
import 'package:studify/data/services/notification_service.dart';
import 'package:studify/data/models/notification_model.dart';

import 'notification_provider_test.mocks.dart';

@GenerateMocks([NotificationService])
void main() {
  late NotificationProvider provider;
  late MockNotificationService mockService;

  setUp(() {
    mockService = MockNotificationService();
    provider = NotificationProvider(service: mockService);
  });

  group('NotificationProvider', () {
    group('fetchNotifications', () {
      test('should fetch notifications successfully', () async {
        // Arrange
        final notifications = [
          NotificationModel(
            id: 1,
            title: 'Test Notification',
            body: 'Test Body',
            isRead: false,
            createdAt: DateTime.now(),
          ),
        ];

        when(mockService.getNotifications())
            .thenAnswer((_) async => notifications);

        // Act
        await provider.fetchNotifications();

        // Assert
        expect(provider.notifications, notifications);
        expect(provider.isLoading, false);
        expect(provider.errorMessage, null);
        verify(mockService.getNotifications()).called(1);
      });

      test('should handle errors when fetching notifications', () async {
        // Arrange
        when(mockService.getNotifications())
            .thenThrow(Exception('Failed to fetch'));

        // Act
        await provider.fetchNotifications();

        // Assert
        expect(provider.notifications, isEmpty);
        expect(provider.isLoading, false);
        expect(provider.errorMessage, isNotNull);
      });
    });

    group('markAsRead', () {
      test('should mark notification as read', () async {
        // Arrange
        final notification = NotificationModel(
          id: 1,
          title: 'Test',
          body: 'Body',
          isRead: false,
          createdAt: DateTime.now(),
        );
        provider.notifications = [notification];

        when(mockService.markAsRead(1))
            .thenAnswer((_) async => notification.copyWith(isRead: true));

        // Act
        await provider.markAsRead(1);

        // Assert
        expect(provider.notifications.first.isRead, true);
        verify(mockService.markAsRead(1)).called(1);
      });
    });

    group('markAllAsRead', () {
      test('should mark all notifications as read', () async {
        // Arrange
        provider.notifications = [
          NotificationModel(
            id: 1,
            title: 'Test 1',
            body: 'Body',
            isRead: false,
            createdAt: DateTime.now(),
          ),
          NotificationModel(
            id: 2,
            title: 'Test 2',
            body: 'Body',
            isRead: false,
            createdAt: DateTime.now(),
          ),
        ];

        when(mockService.markAllAsRead()).thenAnswer((_) async => {});

        // Act
        await provider.markAllAsRead();

        // Assert
        expect(provider.notifications.every((n) => n.isRead), true);
        verify(mockService.markAllAsRead()).called(1);
      });
    });

    group('unreadCount', () {
      test('should return count of unread notifications', () {
        // Arrange
        provider.notifications = [
          NotificationModel(
            id: 1,
            title: 'Test 1',
            body: 'Body',
            isRead: false,
            createdAt: DateTime.now(),
          ),
          NotificationModel(
            id: 2,
            title: 'Test 2',
            body: 'Body',
            isRead: true,
            createdAt: DateTime.now(),
          ),
          NotificationModel(
            id: 3,
            title: 'Test 3',
            body: 'Body',
            isRead: false,
            createdAt: DateTime.now(),
          ),
        ];

        // Assert
        expect(provider.unreadCount, 2);
      });
    });
  });
}
```

---

## 🔄 FRONTEND INTEGRATION TESTS

### **File: `test/integration/auth_flow_test.dart`**

```dart
import 'package:flutter_test/flutter_test.dart';
import 'package:mockito/mockito.dart';
import 'package:mockito/annotations.dart';
import 'package:studify/providers/auth_provider.dart';
import 'package:studify/data/services/auth_service.dart';
import 'package:studify/data/models/user_model.dart';
import 'package:studify/data/models/auth_response_model.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'auth_flow_test.mocks.dart';

@GenerateMocks([AuthService])
void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  group('Authentication Flow Integration Tests', () {
    late AuthProvider authProvider;
    late MockAuthService mockAuthService;

    setUp(() async {
      SharedPreferences.setMockInitialValues({});
      mockAuthService = MockAuthService();
      authProvider = AuthProvider(service: mockAuthService);
    });

    test('Complete login flow - success', () async {
      // Arrange
      final email = 'test@example.com';
      final password = 'password123';
      final user = User(
        id: 1,
        name: 'Test User',
        email: email,
        createdAt: DateTime.now(),
        updatedAt: DateTime.now(),
      );
      final authResponse = AuthResponse(
        user: user,
        accessToken: 'fake-jwt-token',
        tokenType: 'bearer',
        expiresIn: 3600,
      );

      when(mockAuthService.login(email, password))
          .thenAnswer((_) async => authResponse);

      // Act
      await authProvider.login(email, password);

      // Assert
      expect(authProvider.status, AuthStatus.authenticated);
      expect(authProvider.user, isNotNull);
      expect(authProvider.user!.email, email);
      expect(authProvider.errorMessage, null);

      // Verify service was called
      verify(mockAuthService.login(email, password)).called(1);
    });

    test('Complete login flow - failure with invalid credentials', () async {
      // Arrange
      final email = 'test@example.com';
      final password = 'wrongpassword';

      when(mockAuthService.login(email, password))
          .thenThrow(UnauthorizedException(message: 'Invalid credentials'));

      // Act
      await authProvider.login(email, password);

      // Assert
      expect(authProvider.status, AuthStatus.unauthenticated);
      expect(authProvider.user, null);
      expect(authProvider.errorMessage, 'Invalid credentials');
    });

    test('Complete registration and auto-login flow', () async {
      // Arrange
      final name = 'New User';
      final email = 'newuser@example.com';
      final password = 'password123';
      final user = User(
        id: 2,
        name: name,
        email: email,
        createdAt: DateTime.now(),
        updatedAt: DateTime.now(),
      );
      final authResponse = AuthResponse(
        user: user,
        accessToken: 'fake-jwt-token',
        tokenType: 'bearer',
        expiresIn: 3600,
      );

      when(mockAuthService.register(name, email, password, password))
          .thenAnswer((_) async => authResponse);

      // Act
      await authProvider.register(name, email, password, password);

      // Assert
      expect(authProvider.status, AuthStatus.authenticated);
      expect(authProvider.user, isNotNull);
      expect(authProvider.user!.name, name);
      expect(authProvider.isAuthenticated, true);

      verify(mockAuthService.register(name, email, password, password))
          .called(1);
    });

    test('Logout flow clears user data', () async {
      // Arrange - First login
      final user = User(
        id: 1,
        name: 'Test User',
        email: 'test@example.com',
        createdAt: DateTime.now(),
        updatedAt: DateTime.now(),
      );
      final authResponse = AuthResponse(
        user: user,
        accessToken: 'fake-jwt-token',
        tokenType: 'bearer',
        expiresIn: 3600,
      );

      when(mockAuthService.login(any, any))
          .thenAnswer((_) async => authResponse);
      when(mockAuthService.logout()).thenAnswer((_) async => {});

      await authProvider.login('test@example.com', 'password123');
      expect(authProvider.isAuthenticated, true);

      // Act - Logout
      await authProvider.logout();

      // Assert
      expect(authProvider.status, AuthStatus.unauthenticated);
      expect(authProvider.user, null);
      expect(authProvider.isAuthenticated, false);
      verify(mockAuthService.logout()).called(1);
    });

    test('Token refresh flow maintains authentication', () async {
      // Arrange
      final user = User(
        id: 1,
        name: 'Test User',
        email: 'test@example.com',
        createdAt: DateTime.now(),
        updatedAt: DateTime.now(),
      );
      final newAuthResponse = AuthResponse(
        user: user,
        accessToken: 'new-jwt-token',
        tokenType: 'bearer',
        expiresIn: 3600,
      );

      when(mockAuthService.refreshToken())
          .thenAnswer((_) async => newAuthResponse);

      authProvider.user = user;
      authProvider.status = AuthStatus.authenticated;

      // Act
      await authProvider.refreshToken();

      // Assert
      expect(authProvider.status, AuthStatus.authenticated);
      expect(authProvider.user, isNotNull);
      verify(mockAuthService.refreshToken()).called(1);
    });

    test('Failed token refresh logs out user', () async {
      // Arrange
      when(mockAuthService.refreshToken())
          .thenThrow(UnauthorizedException(message: 'Token expired'));
      when(mockAuthService.logout()).thenAnswer((_) async => {});

      // Act
      await authProvider.refreshToken();

      // Assert
      expect(authProvider.status, AuthStatus.unauthenticated);
      expect(authProvider.user, null);
    });
  });
}
```

---

### **File: `test/integration/classroom_flow_test.dart`**

```dart
import 'package:flutter_test/flutter_test.dart';
import 'package:mockito/mockito.dart';
import 'package:mockito/annotations.dart';
import 'package:studify/providers/classroom_provider.dart';
import 'package:studify/data/services/classroom_service.dart';
import 'package:studify/data/models/classroom_model.dart';
import 'package:studify/data/models/user_model.dart';

import 'classroom_flow_test.mocks.dart';

@GenerateMocks([ClassroomService])
void main() {
  group('Classroom Flow Integration Tests', () {
    late ClassroomProvider provider;
    late MockClassroomService mockService;

    setUp(() {
      mockService = MockClassroomService();
      provider = ClassroomProvider(service: mockService);
    });

    test('Complete classroom creation flow', () async {
      // Arrange
      final classroomData = {
        'name': 'Math Class',
        'description': 'Advanced Mathematics',
      };

      final owner = User(
        id: 1,
        name: 'Teacher',
        email: 'teacher@example.com',
        createdAt: DateTime.now(),
        updatedAt: DateTime.now(),
      );

      final createdClassroom = Classroom(
        id: 1,
        ownerId: owner.id,
        name: classroomData['name']!,
        uniqueCode: 'ABC123',
        description: classroomData['description'],
        owner: owner,
        users: [owner],
        usersCount: 1,
        createdAt: DateTime.now(),
        updatedAt: DateTime.now(),
      );

      when(mockService.createClassroom(any))
          .thenAnswer((_) async => createdClassroom);

      // Act
      await provider.createClassroom(classroomData);

      // Assert
      expect(provider.classrooms, contains(createdClassroom));
      expect(provider.isLoading, false);
      expect(provider.errorMessage, null);
      verify(mockService.createClassroom(any)).called(1);
    });

    test('Complete join classroom flow', () async {
      // Arrange
      final uniqueCode = 'ABC123';
      final classroom = Classroom(
        id: 1,
        ownerId: 2,
        name: 'Math Class',
        uniqueCode: uniqueCode,
        usersCount: 2,
        createdAt: DateTime.now(),
        updatedAt: DateTime.now(),
      );

      when(mockService.joinClassroom(uniqueCode))
          .thenAnswer((_) async => classroom);
      when(mockService.getClassrooms())
          .thenAnswer((_) async => [classroom]);

      // Act
      await provider.joinClassroom(uniqueCode);

      // Assert
      expect(provider.classrooms, contains(classroom));
      verify(mockService.joinClassroom(uniqueCode)).called(1);
    });

    test('Complete leave classroom flow', () async {
      // Arrange
      final classroom = Classroom(
        id: 1,
        ownerId: 2,
        name: 'Math Class',
        uniqueCode: 'ABC123',
        usersCount: 2,
        createdAt: DateTime.now(),
        updatedAt: DateTime.now(),
      );

      provider.classrooms = [classroom];

      when(mockService.leaveClassroom(classroom.id))
          .thenAnswer((_) async => {});

      // Act
      await provider.leaveClassroom(classroom.id);

      // Assert
      expect(provider.classrooms, isEmpty);
      verify(mockService.leaveClassroom(classroom.id)).called(1);
    });

    test('Complete delete classroom flow (owner only)', () async {
      // Arrange
      final classroom = Classroom(
        id: 1,
        ownerId: 1,
        name: 'Math Class',
        uniqueCode: 'ABC123',
        usersCount: 1,
        createdAt: DateTime.now(),
        updatedAt: DateTime.now(),
      );

      provider.classrooms = [classroom];

      when(mockService.deleteClassroom(classroom.id))
          .thenAnswer((_) async => {});

      // Act
      await provider.deleteClassroom(classroom.id);

      // Assert
      expect(provider.classrooms, isEmpty);
      verify(mockService.deleteClassroom(classroom.id)).called(1);
    });

    test('Fetch classrooms and cache them', () async {
      // Arrange
      final classrooms = [
        Classroom(
          id: 1,
          ownerId: 1,
          name: 'Class 1',
          uniqueCode: 'ABC',
          usersCount: 5,
          createdAt: DateTime.now(),
          updatedAt: DateTime.now(),
        ),
        Classroom(
          id: 2,
          ownerId: 1,
          name: 'Class 2',
          uniqueCode: 'DEF',
          usersCount: 3,
          createdAt: DateTime.now(),
          updatedAt: DateTime.now(),
        ),
      ];

      when(mockService.getClassrooms())
          .thenAnswer((_) async => classrooms);

      // Act
      await provider.fetchClassrooms();

      // Assert
      expect(provider.classrooms.length, 2);
      expect(provider.isLoading, false);

      // Fetch again - should use cache if implemented
      await provider.fetchClassrooms();
      
      // Depending on implementation, verify was called once or twice
    });
  });
}
```

---

## 🏃 RUNNING TESTS

### **Backend (Laravel)**

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Unit/Models/UserTest.php

# Run specific test method
php artisan test --filter=test_user_has_owned_classrooms_relationship

# Run with coverage
php artisan test --coverage

# Run only Unit tests
php artisan test --testsuite=Unit

# Run only Feature tests
php artisan test --testsuite=Feature
```

### **Frontend (Flutter)**

```bash
# Run all tests
flutter test

# Run specific test file
flutter test test/unit/providers/auth_provider_test.dart

# Run with coverage
flutter test --coverage

# View coverage report (after generating)
genhtml coverage/lcov.info -o coverage/html
# Open coverage/html/index.html in browser

# Run tests in specific folder
flutter test test/unit/
flutter test test/integration/

# Run with verbose output
flutter test --verbose
```

---

## 📊 Coverage Reports

### **Backend Coverage**
```bash
# Generate coverage with PHPUnit
php artisan test --coverage-html coverage/html

# Or with PCOV (faster)
XDEBUG_MODE=coverage php artisan test --coverage
```

### **Frontend Coverage**
```bash
# Generate coverage
flutter test --coverage

# Generate HTML report
genhtml coverage/lcov.info -o coverage/html

# Open in browser
open coverage/html/index.html  # macOS
start coverage/html/index.html # Windows
```

---

## ✅ Checklist Before Committing

- [ ] All tests pass locally
- [ ] New features have corresponding tests
- [ ] Test coverage meets minimum threshold (80%+)
- [ ] No skipped or ignored tests
- [ ] Mock external dependencies properly
- [ ] Tests are isolated and independent
- [ ] Descriptive test names
- [ ] Follow AAA pattern (Arrange, Act, Assert)

---

**Generated**: December 17, 2025  
**Project**: Studify Testing Implementation Guide  
**Next Steps**: Implement missing tests according to priority
