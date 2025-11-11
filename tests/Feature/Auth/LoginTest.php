<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use function Pest\Laravel\postJson;

it('allows valid credentials and returns personal access token', function () {
    $user = User::factory()->create([
        'email' => 'admin@example.com',
        'password' => Hash::make('secret123'),
    ]);

    RateLimiter::clear($user->email . '|127.0.0.1');

    $response = postJson('/api/v1/auth/login', [
        'email' => $user->email,
        'password' => 'secret123',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.user.email', $user->email)
        ->assertJsonPath('data.tokenType', 'Bearer')
        ->assertJsonStructure([
            'data' => [
                'token',
                'tokenType',
                'expiresIn',
                'user' => ['id', 'name', 'email', 'roles'],
            ],
            'message',
        ]);

    expect($response->json('data.token'))->toBeString()->not->toBeEmpty();
});

it('returns 401 with standard error when credentials invalid', function () {
    RateLimiter::clear('wrong@example.com|127.0.0.1');

    postJson('/api/v1/auth/login', [
        'email' => 'wrong@example.com',
        'password' => 'invalid',
    ])->assertStatus(401)
        ->assertJsonStructure([
            'message',
            'code',
            'errors',
            'trace_id',
        ])
        ->assertJsonPath('code', 'AUTH_INVALID_CREDENTIAL');
});

it('throttles consecutive failed attempts after limit', function () {
    RateLimiter::clear('throttle@example.com|127.0.0.1');

    foreach (range(1, 5) as $attempt) {
        postJson('/api/v1/auth/login', [
            'email' => 'throttle@example.com',
            'password' => 'invalid',
        ])->assertStatus(401);
    }

    postJson('/api/v1/auth/login', [
        'email' => 'throttle@example.com',
        'password' => 'invalid',
    ])->assertStatus(429)
        ->assertJsonPath('code', 'AUTH_RATE_LIMIT_EXCEEDED');
});