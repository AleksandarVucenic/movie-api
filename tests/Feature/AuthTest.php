<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['data' => ['user', 'token']]);

        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }

    public function test_user_cannot_register_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);

        $this->postJson('/api/register', [
            'name' => 'Another User',
            'email' => 'taken@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertUnprocessable();
    }

    public function test_user_can_login(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password',
        ])->assertOk()
            ->assertJsonStructure(['data' => ['user', 'token']]);
    }

    public function test_user_cannot_login_with_wrong_password(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ])->assertUnprocessable();
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $this->withToken($token)->postJson('/api/logout')
            ->assertOk();
    }

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $this->getJson('/api/watchlist')->assertUnauthorized();
    }

    public function test_auth_endpoints_are_rate_limited(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/login', [
                'email'    => $user->email,
                'password' => 'wrong-password',
            ]);
        }

        $this->postJson('/api/login', [
            'email'    => $user->email,
            'password' => 'wrong-password',
        ])->assertStatus(429);
    }
}
