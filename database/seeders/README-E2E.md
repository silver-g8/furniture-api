# E2E Test Data Seeder

This seeder creates predictable test data for E2E (End-to-End) testing.

## What it creates

### Admin User
- **Email**: `admin@example.com`
- **Password**: `password`
- **Role**: admin (if Spatie Permission is installed)

### Categories
- Furniture (parent)
  - Living Room
  - Bedroom
  - Dining Room

### Products
16 test products with predictable SKUs starting with `E2E-`:
- 5 main products (Sofa, Coffee Table, Bed, Wardrobe, Dining Set)
- 1 inactive product (for filter testing)
- 10 additional products (for pagination testing)

All products have:
- Predictable SKUs (E2E-SOFA-001, E2E-TABLE-001, etc.)
- Consistent naming (E2E Test Product X)
- Various categories for filter testing
- Mix of active/inactive status

## Usage

### Run all seeders (including E2E data)
```bash
php artisan db:seed
```

### Run only E2E test seeder
```bash
php artisan db:seed --class=E2ETestSeeder
```

### Fresh migration with all seeders
```bash
php artisan migrate:fresh --seed
```

### Fresh migration with only E2E seeder
```bash
php artisan migrate:fresh
php artisan db:seed --class=E2ETestSeeder
```

## For E2E Tests

The frontend E2E tests expect:
1. Backend API running at `http://localhost:8000`
2. Database seeded with E2E test data
3. Admin user credentials: `admin@example.com` / `password`

### Quick Setup for E2E Testing
```bash
# In backend directory
cd ~/projects/furniture-api/app
php artisan migrate:fresh --seed
php artisan serve --port=8000

# In frontend directory (new terminal)
cd ~/projects/fui-furniture
npx playwright test
```

## Notes

- Uses `firstOrCreate()` to avoid duplicates
- Safe to run multiple times
- Products use `E2E-` prefix to distinguish from regular test data
- Includes enough products (16) to test pagination (typically 15 per page)
- สำหรับข้อมูล Catalog CRUD พื้นฐาน ดู `database/seeders/README-Catalog.md`
