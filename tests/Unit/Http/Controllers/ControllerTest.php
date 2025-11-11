<?php

namespace Tests\Unit\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Container\Container;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

use function json_decode;

use PHPUnit\Framework\TestCase;

class ControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Container::setInstance(new Container);
        app()->instance('request', Request::create('/', 'GET'));
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);

        parent::tearDown();
    }

    public function test_respond_success_wraps_payload(): void
    {
        $controller = new class extends Controller
        {
            public function call(): JsonResponse
            {
                return $this->respondSuccess(['foo' => 'bar'], 'Completed', 201);
            }
        };

        $response = $controller->call();
        $this->assertSame(201, $response->getStatusCode());

        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(['foo' => 'bar'], $payload['data']);
        $this->assertSame('Completed', $payload['message']);
    }

    public function test_respond_error_wraps_payload_and_generates_trace_id(): void
    {
        $controller = new class extends Controller
        {
            public function call(): JsonResponse
            {
                return $this->respondError('Invalid credentials', 'AUTH_INVALID_CREDENTIAL', ['email' => ['Invalid']], 422);
            }
        };

        $response = $controller->call();
        $this->assertSame(422, $response->getStatusCode());

        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('Invalid credentials', $payload['message']);
        $this->assertSame('AUTH_INVALID_CREDENTIAL', $payload['code']);
        $this->assertArrayHasKey('errors', $payload);
        $this->assertSame(['email' => ['Invalid']], (array) $payload['errors']);
        $this->assertArrayHasKey('trace_id', $payload);
        $this->assertNotEmpty($payload['trace_id']);
    }
}
