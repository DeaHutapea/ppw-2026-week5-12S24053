<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    private function pustakawan(): User
    {
        return User::factory()->create([
            'email' => 'pustakawan@del.ac.id',
            'password' => 'password123',
        ]);
    }

    public function test_login_issues_bearer_token(): void
    {
        $this->pustakawan();

        $this->postJson('/api/v1/auth/login', [
            'email' => 'pustakawan@del.ac.id',
            'password' => 'password123',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonStructure(['data' => ['access_token', 'user' => ['id', 'name', 'email']]]);

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_login_with_wrong_password_returns_401_problem_details(): void
    {
        $this->pustakawan();

        $this->postJson('/api/v1/auth/login', [
            'email' => 'pustakawan@del.ac.id',
            'password' => 'salah-total',
        ])
            ->assertStatus(401)
            ->assertJsonPath('status', 401)
            ->assertJsonStructure(['type', 'title', 'status', 'detail', 'instance']);
    }

    public function test_login_with_empty_body_returns_422_with_errors(): void
    {
        $this->postJson('/api/v1/auth/login', [])
            ->assertStatus(422)
            ->assertJsonPath('status', 422)
            ->assertJsonStructure(['type', 'title', 'status', 'detail', 'instance', 'errors' => ['email', 'password']]);
    }

    public function test_me_requires_bearer_token(): void
    {
        $this->getJson('/api/v1/auth/me')
            ->assertStatus(401)
            ->assertJsonPath('title', 'Unauthorized');
    }

    public function test_me_and_logout_work_with_valid_token(): void
    {
        $user = $this->pustakawan();
        $token = $user->createToken('test')->plainTextToken;
        $auth = ['Authorization' => 'Bearer '.$token];

        $this->getJson('/api/v1/auth/me', $auth)
            ->assertOk()
            ->assertJsonPath('data.email', 'pustakawan@del.ac.id');

        $this->postJson('/api/v1/auth/logout', [], $auth)->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_unknown_api_endpoint_returns_404_problem_details(): void
    {
        $this->getJson('/api/v1/tidak-ada')
            ->assertStatus(404)
            ->assertJsonPath('status', 404);
    }
}