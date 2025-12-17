<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Test user can register successfully.
     *
     * @return void
     */
    public function test_user_can_register_successfully()
    {
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->postJson('/api/users', $userData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'name', 'email'],
                    'access_token',
                    'token_type',
                    'expires_in'
                ]
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);
    }

    /**
     * Test user registration fails with invalid data.
     *
     * @return void
     */
    public function test_user_registration_fails_with_invalid_data()
    {
        $response = $this->postJson('/api/users', [
            'name' => '',
            'email' => 'invalid-email',
            'password' => '123',
        ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'message',
                'errors'
            ]);
    }

    /**
     * Test user registration fails with duplicate email.
     *
     * @return void
     */
    public function test_user_registration_fails_with_duplicate_email()
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->postJson('/api/users', [
            'name' => 'Test User',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * Test user registration fails when password confirmation doesn't match.
     *
     * @return void
     */
    public function test_user_registration_fails_when_password_confirmation_doesnt_match()
    {
        $response = $this->postJson('/api/users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different_password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /**
     * Test user can login successfully.
     *
     * @return void
     */
    public function test_user_can_login_successfully()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'name', 'email'],
                    'access_token',
                    'token_type',
                    'expires_in'
                ]
            ]);
    }

    /**
     * Test user login fails with invalid credentials.
     *
     * @return void
     */
    public function test_user_login_fails_with_invalid_credentials()
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrong_password',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Invalid email or password',
            ]);
    }

    /**
     * Test user login fails with non-existent email.
     *
     * @return void
     */
    public function test_user_login_fails_with_non_existent_email()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Invalid email or password',
            ]);
    }

    /**
     * Test user login fails with invalid data format.
     *
     * @return void
     */
    public function test_user_login_fails_with_invalid_data_format()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'invalid-email',
            'password' => '123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    /**
     * Test authenticated user can get their profile.
     *
     * @return void
     */
    public function test_authenticated_user_can_get_profile()
    {
        $user = User::factory()->create();
        /** @var \Tymon\JWTAuth\JWTGuard $auth */
        $auth = auth();
        $token = $auth->login($user);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/auth/user');

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->name,
                ]
            ]);
    }

    /**
     * Test unauthenticated user cannot get profile.
     *
     * @return void
     */
    public function test_unauthenticated_user_cannot_get_profile()
    {
        $response = $this->getJson('/api/auth/user');

        $response->assertStatus(401);
    }

    /**
     * Test authenticated user can logout.
     *
     * @return void
     */
    public function test_authenticated_user_can_logout()
    {
        $user = User::factory()->create();
        /** @var \Tymon\JWTAuth\JWTGuard $auth */
        $auth = auth();
        $token = $auth->login($user);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->deleteJson('/api/auth/login');

        $response->assertStatus(204);
    }

    /**
     * Test unauthenticated user cannot logout.
     *
     * @return void
     */
    public function test_unauthenticated_user_cannot_logout()
    {
        $response = $this->deleteJson('/api/auth/login');

        $response->assertStatus(401);
    }

    /**
     * Test authenticated user can refresh token.
     *
     * @return void
     */
    public function test_authenticated_user_can_refresh_token()
    {
        $user = User::factory()->create();
        /** @var \Tymon\JWTAuth\JWTGuard $auth */
        $auth = auth();
        $token = $auth->login($user);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/auth/refresh');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'user',
                    'access_token',
                    'token_type',
                    'expires_in'
                ]
            ]);
    }

    /**
     * Test unauthenticated user cannot refresh token.
     *
     * @return void
     */
    public function test_unauthenticated_user_cannot_refresh_token()
    {
        $response = $this->postJson('/api/auth/refresh');

        $response->assertStatus(401);
    }

    /**
     * Test token is invalid after logout.
     *
     * @return void
     */
    public function test_token_is_invalid_after_logout()
    {
        $user = User::factory()->create();
        /** @var \Tymon\JWTAuth\JWTGuard $auth */
        $auth = auth();
        $token = $auth->login($user);

        // Logout
        $this->withHeader('Authorization', "Bearer $token")
            ->deleteJson('/api/auth/login');

        // Try to use the same token
        $response = $this->withHeader('Authorization', "Bearer $token")
            ->getJson('/api/auth/user');

        $response->assertStatus(401);
    }
    /**
     * Test user can update profile.
     *
     * @return void
     */
    public function test_user_can_update_profile()
    {
        $user = User::factory()->create(['email' => 'original@example.com']);
        /** @var \Tymon\JWTAuth\JWTGuard $auth */
        $auth = auth();
        $token = $auth->login($user);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/auth/profile', [
                'name' => 'Updated Name',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'message' => 'Profile updated successfully',
                'data' => [
                    'id' => $user->id,
                    'name' => 'Updated Name',
                    'email' => 'original@example.com',
                ]
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Updated Name',
            'email' => 'original@example.com',
        ]);
    }

    /**
     * Test user cannot_update_profile_with_invalid_data.
     *
     * @return void
     */
    public function test_user_cannot_update_profile_with_invalid_data()
    {
        $user = User::factory()->create();
        /** @var \Tymon\JWTAuth\JWTGuard $auth */
        $auth = auth();
        $token = $auth->login($user);

        $response = $this->withHeader('Authorization', "Bearer $token")
            ->postJson('/api/auth/profile', [
                'name' => '',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
}
