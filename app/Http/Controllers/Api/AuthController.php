<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    #[OA\Post(
        path: '/api/v1/auth/login',
        summary: 'User login',
        description: 'Authenticate user and return access token',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@example.com'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password'),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login successful',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/AuthLoginSuccess'),
                        new OA\Property(property: 'message', type: 'string'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Invalid credentials',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
            new OA\Response(
                response: 429,
                description: 'Too many attempts',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
        ]
    )]
    public function login(LoginRequest $request): JsonResponse
    {
        if (($seconds = $request->ensureIsNotRateLimited()) !== null) {
            return $this->respondError(
                __('auth.throttle', ['seconds' => $seconds]),
                'AUTH_RATE_LIMIT_EXCEEDED',
                [],
                429
            );
        }

        /** @var User|null $user */
        $user = User::where('email', $request->validated('email'))->first();

        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            RateLimiter::hit($request->throttleKey());

            return $this->respondError(
                'à¸­à¸µà¹€à¸¡à¸¥à¸«à¸£à¸·à¸­à¸£à¸«à¸±à¸ªà¸œà¹ˆà¸²à¸™à¹„à¸¡à¹ˆà¸–à¸¹à¸à¸•à¹‰à¸­à¸‡',
                'AUTH_INVALID_CREDENTIAL',
                [],
                401
            );
        }

        RateLimiter::clear($request->throttleKey());

        $token = $user->createToken('api')->plainTextToken;
        $expiresMinutes = config('sanctum.expiration');
        $expiresIn = $expiresMinutes ? $expiresMinutes * 60 : null;

        $user->loadMissing('roles');

        return $this->respondSuccess([
            'token' => $token,
            'tokenType' => 'Bearer',
            'expiresIn' => $expiresIn,
            'user' => UserResource::make($user)->resolve(),
        ], 'à¹€à¸‚à¹‰à¸²à¸ªà¸¹à¹ˆà¸£à¸°à¸šà¸šà¸ªà¸³à¹€à¸£à¹‡à¸ˆ');
    }

    #[OA\Get(
        path: '/api/v1/auth/me',
        summary: 'Get current user',
        description: 'Get authenticated user information',
        security: [['bearerAuth' => []]],
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'User information',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/AuthMeSuccess'),
                        new OA\Property(property: 'message', type: 'string'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
        ]
    )]
    public function me(Request $request): JsonResponse
    {
        $token = $request->bearerToken();

        if ($token !== null && PersonalAccessToken::findToken($token) === null) {
            return $this->respondError(
                'ไม่มีสิทธิ์เข้าถึง',
                'AUTH_TOKEN_INVALID',
                [],
                401,
            );
        }

        /** @var User|null $user */
        $user = $request->user();

        if (! $user instanceof User) {
            return $this->respondError(
                'ไม่มีสิทธิ์เข้าถึง',
                'AUTH_UNAUTHENTICATED',
                [],
                401,
            );
        }

        $user->loadMissing('roles');

        return $this->respondSuccess([
            'user' => UserResource::make($user)->resolve(),
        ], 'โหลดข้อมูลผู้ใช้สำเร็จ');
    }

    #[OA\Post(
        path: '/api/v1/auth/logout',
        summary: 'User logout',
        description: 'Revoke current access token',
        security: [['bearerAuth' => []]],
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Logout successful',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'null', nullable: true),
                        new OA\Property(property: 'message', type: 'string'),
                    ]
                )
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/Error')
            ),
        ]
    )]
    public function logout(Request $request): JsonResponse
    {
        $accessToken = $request->user()?->currentAccessToken();

        if ($accessToken !== null) {
            $accessToken->delete();
        } else {
            $token = $request->bearerToken();
            if ($token) {
                $plain = Str::contains($token, '|')
                    ? explode('|', $token, 2)[1]
                    : $token;

                $hashed = hash('sha256', $plain);
                PersonalAccessToken::where('token', $hashed)->delete();
            }
        }

        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return $this->respondSuccess(null, 'ออกจากระบบสำเร็จ');
    }
}
