<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Cache\RateLimiter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\RateLimiter as RateLimiterFacade;
use Illuminate\Support\Str;

class LoginRequest extends FormRequest
{
    private const MAX_ATTEMPTS = 5;

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:6'],
        ];
    }

    /**
     * @return int|null seconds remaining before next attempt is allowed when throttled
     */
    public function ensureIsNotRateLimited(): ?int
    {
        $limiter = $this->limiter();
        $key = $this->throttleKey();

        if (! $limiter->tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            return null;
        }

        return $limiter->availableIn($key);
    }

    public function throttleKey(): string
    {
        return Str::lower($this->input('email', '')).'|'.$this->ip();
    }

    public function limiter(): RateLimiter
    {
        /** @var RateLimiter $limiter */
        $limiter = RateLimiterFacade::getFacadeRoot();

        return $limiter;
    }
}
