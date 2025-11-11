<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            [
                'sku' => 'ALPHA-SOFA-001',
                'name' => 'Alpha Modular Sofa',
                'price' => 32900.00,
                'status' => 'active',
                'category_slug' => 'sofas',
                'brand_slug' => 'alpha-furniture',
            ],
            [
                'sku' => 'ALPHA-SOFA-002',
                'name' => 'Alpha Recliner Chair',
                'price' => 21900.00,
                'status' => 'inactive',
                'category_slug' => 'sofas',
                'brand_slug' => 'alpha-furniture',
            ],
            [
                'sku' => 'ALPHA-TABLE-001',
                'name' => 'Timber Coffee Table',
                'price' => 7900.00,
                'status' => 'active',
                'category_slug' => 'coffee-tables',
                'brand_slug' => 'alpha-furniture',
            ],
            [
                'sku' => 'ALPHA-TV-001',
                'name' => 'Lowline TV Console',
                'price' => 12500.00,
                'status' => 'active',
                'category_slug' => 'tv-stands',
                'brand_slug' => 'alpha-furniture',
            ],
            [
                'sku' => 'BETA-BED-001',
                'name' => 'Beta King Bedframe',
                'price' => 28900.00,
                'status' => 'active',
                'category_slug' => 'beds',
                'brand_slug' => 'beta-living',
            ],
            [
                'sku' => 'BETA-WARD-001',
                'name' => 'Sliding Wardrobe Duo',
                'price' => 19900.00,
                'status' => 'active',
                'category_slug' => 'wardrobes',
                'brand_slug' => 'beta-living',
            ],
            [
                'sku' => 'CRAFT-DINE-001',
                'name' => 'Crafted Oak Dining Set',
                'price' => 35900.00,
                'status' => 'active',
                'category_slug' => 'dining-sets',
                'brand_slug' => 'crafted-comfort',
            ],
            [
                'sku' => 'CRAFT-BAR-001',
                'name' => 'Compact Bar Cabinet',
                'price' => 15400.00,
                'status' => 'archived',
                'category_slug' => 'bar-cabinets',
                'brand_slug' => 'crafted-comfort',
            ],
            [
                'sku' => 'NORD-LIGHT-001',
                'name' => 'Nordic Pendant Light',
                'price' => 6400.00,
                'status' => 'active',
                'category_slug' => 'ceiling-lights',
                'brand_slug' => 'nordic-lights',
            ],
            [
                'sku' => 'NORD-LAMP-001',
                'name' => 'Scandi Table Lamp',
                'price' => 3200.00,
                'status' => 'draft',
                'category_slug' => 'table-lamps',
                'brand_slug' => 'nordic-lights',
            ],
            [
                'sku' => 'LIGHT-FLOOR-001',
                'name' => 'Minimalist Floor Lamp',
                'price' => 5200.00,
                'status' => 'active',
                'category_slug' => 'floor-lamps',
                'brand_slug' => 'nordic-lights',
            ],
            [
                'sku' => 'URBN-PATIO-001',
                'name' => 'Urban Outdoor Lounge',
                'price' => 42900.00,
                'status' => 'archived',
                'category_slug' => 'patio-sets',
                'brand_slug' => 'urban-retreat',
            ],
            [
                'sku' => 'URBN-GARDEN-001',
                'name' => 'Garden Lantern Set',
                'price' => 8900.00,
                'status' => 'active',
                'category_slug' => 'garden-decor',
                'brand_slug' => 'urban-retreat',
            ],
            [
                'sku' => 'DECOR-MIRROR-001',
                'name' => 'Round Accent Mirror',
                'price' => 4500.00,
                'status' => 'active',
                'category_slug' => 'mirrors',
                'brand_slug' => null,
            ],
        ];

        foreach ($products as $product) {
            $category = Category::where('slug', $product['category_slug'])->first();

            if (! $category) {
                $this->command?->warn(sprintf('Skip seeding product %s: category %s not found', $product['sku'], $product['category_slug']));

                continue;
            }

            $brandId = null;

            if ($product['brand_slug']) {
                $brand = Brand::where('slug', $product['brand_slug'])->first();

                if (! $brand) {
                    $this->command?->warn(sprintf('Skip seeding product %s: brand %s not found', $product['sku'], $product['brand_slug']));

                    continue;
                }

                $brandId = $brand->id;
            }

            Product::updateOrCreate(
                ['sku' => $product['sku']],
                [
                    'name' => $product['name'],
                    'price' => $product['price'],
                    'status' => $product['status'],
                    'category_id' => $category->id,
                    'brand_id' => $brandId,
                ]
            );
        }

        Product::factory()
            ->count(100)
            ->create();
    }
}
