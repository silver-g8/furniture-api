<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\getJson;

it('requires authentication for /auth/me endpoint', function () {
    getJson('/api/v1/auth/me')->assertStatus(401);
});

it('returns current user summary when token valid', function () {
    $user = User::factory()->create([
        'email' => 'member@example.com',
        'password' => Hash::make('secret123'),
    ]);

    $token = $user->createToken('api')->plainTextToken;

    getJson('/api/v1/auth/me', [
        'Authorization' => "Bearer {$token}",
    ])->assertOk()
        ->assertJsonPath('data.user.email', $user->email)
        ->assertJsonStructure([
            'data' => [
                'user' => [
                    'id',
                    'name',
                    'email',
                    'roles',
                ],
            ],
            'message',
        ]);
});
