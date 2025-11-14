# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Furniture API** - Laravel 12 REST API backend for the Roiet furniture inventory management system.

### Technology Stack
- **Framework**: Laravel 12 (PHP 8.2+)
- **Authentication**: Laravel Sanctum (token-based API authentication)
- **Database**: MySQL 8.0+
- **API Documentation**: darkaonline/l5-swagger (Swagger/OpenAPI)
- **Image Processing**: intervention/image ^3.0
- **Testing**: Pest PHP 3 with Laravel plugin
- **Code Quality**: Laravel Pint (code style), Larastan (static analysis)
- **Development**: Laravel Sail, Laravel Pail (log viewer)

### Repository Structure
```
C:\Users\silve\
├── Herd\
│   └── furniture-api\        # This Laravel backend API
└── project\roiet\
    ├── fui-furniture\         # Vue 3 + Quasar frontend (separate repo)
    └── specs\main\            # Feature specification documents
```

**Important**: When referencing the frontend, use `C:/Users/silve/project/roiet/fui-furniture/` or relative path `../../project/roiet/fui-furniture/`.

## Development Commands

### Setup
```bash
# Install PHP dependencies
composer install

# Configure environment (required before first run)
cp .env.example .env
php artisan key:generate

# Run migrations and seeders
php artisan migrate --seed

# Generate Swagger API documentation
php artisan l5-swagger:generate
```

### Development
```bash
# Start development server (via Laravel Herd or Valet)
# URL: http://furniture-api.test

# Or use artisan serve
php artisan serve

# Watch logs in real-time
php artisan pail

# Run tests
php artisan test
# or
./vendor/bin/pest

# Run type coverage
./vendor/bin/pest --type-coverage

# Run static analysis
./vendor/bin/phpstan analyse

# Format code
./vendor/bin/pint
```

### Database
```bash
# Run migrations
php artisan migrate

# Rollback migrations
php artisan migrate:rollback

# Fresh migration with seeders
php artisan migrate:fresh --seed

# Create new migration
php artisan make:migration create_table_name

# Create model with migration
php artisan make:model ModelName -m
```

## Architecture

### API Structure
- **Base URL**: `http://furniture-api.test/api/v1`
- **API Routes**: Defined in `routes/api.php`
- **API Documentation**: Available at `/api/documentation` (Swagger UI)
- **Versioning**: All routes prefixed with `/api/v1`

### Authentication & Authorization
- **Method**: Laravel Sanctum token-based authentication
- **Login endpoint**: `POST /api/v1/auth/login` → Returns bearer token
- **Logout endpoint**: `POST /api/v1/auth/logout`
- **Protected routes**: Use `auth:sanctum` middleware
- **CORS**: Configured in `config/cors.php` to allow frontend origin
- **CSRF**: Stateful API enabled for same-domain requests

### Application Layers

#### 1. Controllers (`app/Http/Controllers/`)
- Thin controllers that delegate business logic to Services
- Return JSON responses using API Resources
- Handle HTTP concerns only (request validation, response formatting)

#### 2. Services (`app/Services/`)
Domain-specific business logic organized by module:
- `Audit/` - Audit logging and tracking
- `Installation/` - Installation and setup services
- `Procurement/` - Purchase orders, suppliers
- `Returns/` - Return management
- `Sales/` - Sales transactions

#### 3. Models (`app/Models/`)
- Eloquent ORM models with relationships
- Use `Concerns/` directory for reusable traits
- Follow naming conventions: singular, PascalCase

#### 4. Requests (`app/Http/Requests/`)
- Form Request validation classes
- Authorize and validate incoming requests
- Return structured validation errors

#### 5. Resources (`app/Http/Resources/`)
- API response transformation layer
- Format model data for JSON responses
- Hide sensitive attributes

#### 6. API Documentation (`app/Docs/`)
- `Controllers/` - Swagger annotations for endpoints
- `Schemas/` - OpenAPI schema definitions
- `Examples/` - Request/response examples

### Database Conventions
- **Migrations**: Use descriptive names with timestamps
- **Table names**: Plural, snake_case (e.g., `product_categories`)
- **Foreign keys**: `{table}_id` format (e.g., `category_id`)
- **Timestamps**: Always include `created_at`, `updated_at`
- **Soft deletes**: Use `deleted_at` for models that need soft delete

### API Response Format
```json
{
  "data": [...],
  "meta": {
    "current_page": 1,
    "total": 100
  },
  "links": {
    "first": "...",
    "last": "...",
    "prev": null,
    "next": "..."
  }
}
```

### Error Response Format
```json
{
  "message": "Validation error",
  "errors": {
    "field_name": ["Error message"]
  }
}
```

## Code Conventions

### Response Language
- **Always respond in Thai** in chat/message context (per project convention)

### PHP/Laravel Standards
- Follow PSR-12 coding standards (enforced by Laravel Pint)
- Use type hints for parameters and return types
- Use strict types: `declare(strict_types=1);`
- Use PHP 8.2+ features (readonly properties, enums, etc.)

### Controller Pattern
```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Resources\ProductResource;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $products = $this->productService->getAllProducts();

        return ProductResource::collection($products);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->productService->createProduct(
            $request->validated()
        );

        return (new ProductResource($product))
            ->response()
            ->setStatusCode(201);
    }
}
```

### Service Pattern
```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

class ProductService
{
    public function getAllProducts(): Collection
    {
        return Product::with(['brand', 'category'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function createProduct(array $data): Product
    {
        return Product::create($data);
    }
}
```

### Request Validation Pattern
```php
<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // or implement authorization logic
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'unique:products'],
            'price' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'กรุณากรอกชื่อสินค้า',
            'sku.unique' => 'รหัสสินค้านี้มีอยู่ในระบบแล้ว',
        ];
    }
}
```

## Testing

### Test Structure
- Unit tests in `tests/Unit/`
- Feature tests in `tests/Feature/`
- Use Pest PHP syntax (preferred over PHPUnit)
- Database tests use `RefreshDatabase` trait

### Pest Test Example
```php
<?php

use App\Models\Product;
use function Pest\Laravel\{postJson, getJson};

it('can create a product', function () {
    $data = [
        'name' => 'Test Product',
        'sku' => 'TEST-001',
        'price' => 1000,
    ];

    postJson('/api/v1/products', $data)
        ->assertCreated()
        ->assertJsonStructure(['data' => ['id', 'name', 'sku']]);

    expect(Product::where('sku', 'TEST-001')->exists())->toBeTrue();
});

it('requires authentication to create product', function () {
    postJson('/api/v1/products', [])
        ->assertUnauthorized();
});
```

## API Documentation

### Swagger/OpenAPI
- API docs available at: `http://furniture-api.test/api/documentation`
- Annotations located in `app/Docs/Controllers/`
- Regenerate docs: `php artisan l5-swagger:generate`

### Example Swagger Annotation
```php
/**
 * @OA\Get(
 *     path="/api/v1/products",
 *     tags={"Products"},
 *     summary="Get list of products",
 *     security={{"sanctum":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Successful operation",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Product"))
 *         )
 *     )
 * )
 */
```

## Common Patterns

### Eager Loading Relationships
```php
// Good - prevents N+1 queries
$products = Product::with(['brand', 'category'])->get();

// Bad - causes N+1 queries
$products = Product::all();
foreach ($products as $product) {
    echo $product->brand->name; // Additional query per product
}
```

### Transaction Handling
```php
use Illuminate\Support\Facades\DB;

DB::transaction(function () use ($data) {
    $product = Product::create($data['product']);
    $product->variants()->createMany($data['variants']);
});
```

### Query Scopes
```php
// In Model
public function scopeActive($query)
{
    return $query->where('is_active', true);
}

// Usage
Product::active()->get();
```

## Integration with Frontend

### CORS Configuration
- Allowed origin: `http://localhost:9000` (Quasar dev server)
- Allowed methods: GET, POST, PUT, PATCH, DELETE
- Credentials: Supported (`Access-Control-Allow-Credentials: true`)

### Sanctum Configuration
- Stateful domains configured in `config/sanctum.php`
- Frontend must send CSRF token from `/sanctum/csrf-cookie`
- Token prefix: `Bearer {token}`

### Image Uploads
- Store in `storage/app/public/images/`
- Access via symlink: `public/storage/images/`
- Create symlink: `php artisan storage:link`
- Process images with Intervention Image package

## Environment Variables

### Required .env Variables
```env
APP_NAME="Furniture API"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://furniture-api.test

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=furniture_api
DB_USERNAME=root
DB_PASSWORD=

# Frontend URL for CORS
FRONTEND_URL=http://localhost:9000

# Sanctum stateful domains
SANCTUM_STATEFUL_DOMAINS=localhost:9000

# Session configuration
SESSION_DOMAIN=.test
```

## Active Modules

Based on Services directory structure:
- **Audit** - System audit logging
- **Installation** - Application installation and setup
- **Procurement** - Purchase orders, supplier management
- **Returns** - Product return processing
- **Sales** - Sales transactions and orders

### Catalog Features (Core)
- Products: CRUD with SKU, pricing, inventory tracking
- Brands: Brand management with logos
- Categories: Hierarchical category structure
- Stock: Inventory adjustments and tracking

## Deployment Checklist

- [ ] Set `APP_ENV=production`
- [ ] Set `APP_DEBUG=false`
- [ ] Generate production key: `php artisan key:generate`
- [ ] Cache configuration: `php artisan config:cache`
- [ ] Cache routes: `php artisan route:cache`
- [ ] Cache views: `php artisan view:cache`
- [ ] Run migrations: `php artisan migrate --force`
- [ ] Link storage: `php artisan storage:link`
- [ ] Set proper permissions on `storage/` and `bootstrap/cache/`
- [ ] Configure queue worker for background jobs
- [ ] Set up scheduled tasks in cron

## Useful Artisan Commands

```bash
# Clear all caches
php artisan optimize:clear

# Create controller with resource methods
php artisan make:controller Api/V1/ProductController --api --model=Product

# Create request validation
php artisan make:request StoreProductRequest

# Create resource
php artisan make:resource ProductResource

# Create service
php artisan make:class Services/ProductService

# Create enum
php artisan make:enum ProductStatus

# List all routes
php artisan route:list

# Check queue jobs
php artisan queue:work

# Run scheduler (add to cron)
php artisan schedule:run
```

## Troubleshooting

### Common Issues
- **CORS errors**: Check `config/cors.php` and `FRONTEND_URL` in `.env`
- **401 Unauthorized**: Verify Sanctum token is sent in `Authorization: Bearer {token}` header
- **500 errors**: Check `storage/logs/laravel.log` or use `php artisan pail`
- **Migration errors**: Ensure database connection is correct in `.env`

### Debug Mode
```bash
# Real-time log monitoring
php artisan pail

# Tinker for testing code
php artisan tinker
```
