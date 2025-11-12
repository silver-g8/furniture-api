<?php

declare(strict_types=1);

namespace App\Docs;

use OpenApi\Attributes as OA;

#[OA\OpenApi(
    security: [
        ['bearerAuth' => []],
    ],
)]
#[OA\Info(
    version: '1.0.0',
    title: 'Furniture System API',
    description: 'Unified API specification for the Furniture ERP system covering all modules: Auth, Catalog, Inventory, Sales, Installation, Procurement, Returns, and Audit.',
    contact: new OA\Contact(
        name: 'API Support',
        email: 'api@furniture-system.local'
    )
)]
#[OA\Server(
    url: 'http://localhost:8000/api/v1',
    description: 'Local Development Server'
)]
#[OA\Server(
    url: 'https://staging-api.furniture-system.local/api/v1',
    description: 'Staging Server'
)]
#[OA\Server(
    url: 'https://api.furniture-system.local/api/v1',
    description: 'Production Server'
)]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    description: 'Personal Access Token issued by Laravel Sanctum. Use the format: Bearer {token}',
    name: 'Authorization',
    in: 'header',
    scheme: 'bearer',
    bearerFormat: 'Sanctum PAT'
)]
#[OA\Tag(
    name: 'Auth',
    description: 'Authentication and authorization endpoints'
)]
#[OA\Tag(
    name: 'Users',
    description: 'User management endpoints'
)]
#[OA\Tag(
    name: 'Catalog',
    description: 'Product catalog management (products, categories, brands)'
)]
#[OA\Tag(
    name: 'Inventory',
    description: 'Warehouse and stock management'
)]
#[OA\Tag(
    name: 'Sales',
    description: 'Sales orders, customers, and payments'
)]
#[OA\Tag(
    name: 'Installation',
    description: 'Installation order management and scheduling'
)]
#[OA\Tag(
    name: 'Procurement',
    description: 'Purchase orders, suppliers, and goods receipt'
)]
#[OA\Tag(
    name: 'Returns',
    description: 'Sales and purchase returns management'
)]
#[OA\Tag(
    name: 'Audit',
    description: 'Audit log tracking and compliance'
)]
class OpenApiBase
{
    // This class exists solely to hold OpenAPI base annotations
}
