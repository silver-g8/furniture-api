<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

it('revokes current token on logout', function () {
    $user = User::factory()->create([
        'email' => 'logout@example.com',
        'password' => Hash::make('secret123'),
    ]);

    $token = $user->createToken('api')->plainTextToken;

    postJson('/api/v1/auth/logout', [], [
        'Authorization' => "Bearer {$token}",
    ])->assertOk()
        ->assertJsonPath('message', 'ออกจากระบบสำเร็จ');

    getJson('/api/v1/auth/me', [
        'Authorization' => "Bearer {$token}",
    ])->assertStatus(401);
});

it('returns 401 when token invalid', function () {
    postJson('/api/v1/auth/logout', [], [
        'Authorization' => 'Bearer invalid-token',
    ])->assertStatus(401);
});
