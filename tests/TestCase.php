<?php

namespace Tests;

use Illuminate\Foundation\Testing\Concerns\InteractsWithContainer;
use Illuminate\Foundation\Testing\Concerns\InteractsWithTime;
use Illuminate\Foundation\Testing\Concerns\MakesHttpRequests;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use InteractsWithContainer;
    use InteractsWithTime;
    use MakesHttpRequests;

    protected function setUp(): void
    {
        parent::setUp();

        // Disable CSRF protection for API tests
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class);
    }

    public function actingAs($user, $guard = null): static
    {
        parent::actingAs($user, $guard);

        $token = $user->createToken('test-suite');

        $this->withHeader('Authorization', 'Bearer '.$token->plainTextToken);

        return $this;
    }
}
