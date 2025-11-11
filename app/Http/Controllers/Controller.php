<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

abstract class Controller
{
    use AuthorizesRequests;

    protected function respondSuccess(mixed $data = null, string $message = 'OK', int $status = 200, array $headers = []): JsonResponse
    {
        return new JsonResponse([
            'data' => $data,
            'message' => $message,
        ], $status, $headers);
    }

    protected function respondError(string $message, string $code = 'ERROR', array $errors = [], int $status = 400, array $headers = []): JsonResponse
    {
        return new JsonResponse([
            'message' => $message,
            'code' => $code,
            'errors' => (object) $errors,
            'trace_id' => $this->resolveTraceId(),
        ], $status, $headers);
    }

    protected function resolveTraceId(): string
    {
        $request = request();

        $fromHeader = $request->header('X-Trace-Id');
        if ($fromHeader) {
            return $fromHeader;
        }

        $fromRequest = $request->attributes->get('trace_id');
        if ($fromRequest) {
            return (string) $fromRequest;
        }

        return (string) Str::uuid();
    }
}
