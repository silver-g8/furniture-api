<?php

declare(strict_types=1);

namespace App\Docs\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Error',
    title: 'Error Response',
    description: 'Standard error response format',
    required: ['message'],
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
        new OA\Property(property: 'code', type: 'string', example: 'VALIDATION_ERROR'),
        new OA\Property(
            property: 'errors',
            type: 'object',
            additionalProperties: new OA\AdditionalProperties(
                type: 'array',
                items: new OA\Items(type: 'string')
            ),
            example: ['email' => ['The email field is required.']]
        ),
        new OA\Property(property: 'trace_id', type: 'string', format: 'uuid', example: '550e8400-e29b-41d4-a716-446655440000'),
    ]
)]
#[OA\Schema(
    schema: 'PaginationMeta',
    title: 'Pagination Metadata',
    description: 'Standard pagination metadata',
    required: ['current_page', 'per_page', 'total', 'last_page'],
    properties: [
        new OA\Property(property: 'current_page', type: 'integer', example: 1),
        new OA\Property(property: 'per_page', type: 'integer', example: 15),
        new OA\Property(property: 'total', type: 'integer', example: 100),
        new OA\Property(property: 'last_page', type: 'integer', example: 7),
        new OA\Property(property: 'from', type: 'integer', example: 1, nullable: true),
        new OA\Property(property: 'to', type: 'integer', example: 15, nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'IdRef',
    title: 'ID Reference',
    description: 'Simple ID reference',
    required: ['id'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64', example: 1),
    ]
)]
#[OA\Schema(
    schema: 'SuccessMessage',
    title: 'Success Message',
    description: 'Standard success message response',
    required: ['message'],
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Operation completed successfully'),
    ]
)]
#[OA\Schema(
    schema: 'ValidationError',
    title: 'Validation Error',
    description: 'Validation error response with field-specific errors',
    required: ['message', 'errors'],
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
        new OA\Property(
            property: 'errors',
            type: 'object',
            additionalProperties: new OA\AdditionalProperties(
                type: 'array',
                items: new OA\Items(type: 'string')
            ),
            example: [
                'email' => ['The email field is required.'],
                'name' => ['The name must be at least 3 characters.'],
            ]
        ),
    ]
)]
#[OA\Schema(
    schema: 'UserSummary',
    title: 'User Summary',
    description: 'Authenticated user payload',
    required: ['id', 'name', 'email', 'roles'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Admin User'),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin@example.com'),
        new OA\Property(
            property: 'roles',
            type: 'array',
            items: new OA\Items(type: 'string'),
            example: ['admin']
        ),
        new OA\Property(
            property: 'permissions',
            type: 'array',
            items: new OA\Items(type: 'string'),
            nullable: true
        ),
        new OA\Property(property: 'avatar_url', type: 'string', format: 'uri', nullable: true),
        new OA\Property(property: 'last_login_at', type: 'string', format: 'date-time', nullable: true),
    ]
)]
#[OA\Schema(
    schema: 'AuthLoginSuccess',
    title: 'Login Success Payload',
    description: 'Data payload returned when login succeeds',
    required: ['token', 'tokenType', 'expiresIn', 'user'],
    properties: [
        new OA\Property(property: 'token', type: 'string', example: '1|abcdef1234567890'),
        new OA\Property(property: 'tokenType', type: 'string', example: 'Bearer'),
        new OA\Property(property: 'expiresIn', type: 'integer', nullable: true, example: 7200, description: 'Expiration window (seconds) or null when no expiry is configured'),
        new OA\Property(property: 'user', ref: '#/components/schemas/UserSummary'),
    ]
)]
#[OA\Schema(
    schema: 'AuthMeSuccess',
    title: 'Current User Payload',
    description: 'Data payload returned from /auth/me endpoint',
    required: ['user'],
    properties: [
        new OA\Property(property: 'user', ref: '#/components/schemas/UserSummary'),
    ]
)]
class CommonSchemas
{
    // This class exists solely to hold OpenAPI schema annotations
}
