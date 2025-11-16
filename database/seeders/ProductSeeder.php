<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

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
                'price_tagged' => 32900.00,
                'status' => 'active',
                'category_slug' => 'sofas',
                'brand_slug' => 'alpha-furniture',
                'on_hand' => 10,
            ],
            [
                'sku' => 'ALPHA-SOFA-002',
                'name' => 'Alpha Recliner Chair',
                'price_tagged' => 21900.00,
                'status' => 'inactive',
                'category_slug' => 'sofas',
                'brand_slug' => 'alpha-furniture',
                'on_hand' => 0,
            ],
            [
                'sku' => 'ALPHA-TABLE-001',
                'name' => 'Timber Coffee Table',
                'price_tagged' => 7900.00,
                'status' => 'active',
                'category_slug' => 'coffee-tables',
                'brand_slug' => 'alpha-furniture',
                'on_hand' => 1,
            ],
            [
                'sku' => 'ALPHA-TV-001',
                'name' => 'Lowline TV Console',
                'price_tagged' => 12500.00,
                'status' => 'active',
                'category_slug' => 'tv-stands',
                'brand_slug' => 'alpha-furniture',
                'on_hand' => 10,
            ],
            [
                'sku' => 'BETA-BED-001',
                'name' => 'Beta King Bedframe',
                'price_tagged' => 28900.00,
                'status' => 'active',
                'category_slug' => 'beds',
                'brand_slug' => 'beta-living',
                'on_hand' => 5,
            ],
            [
                'sku' => 'BETA-WARD-001',
                'name' => 'Sliding Wardrobe Duo',
                'price_tagged' => 19900.00,
                'status' => 'active',
                'category_slug' => 'wardrobes',
                'brand_slug' => 'beta-living',
                'on_hand' => 1,
            ],
            [
                'sku' => 'CRAFT-DINE-001',
                'name' => 'Crafted Oak Dining Set',
                'price_tagged' => 35900.00,
                'status' => 'active',
                'category_slug' => 'dining-sets',
                'brand_slug' => 'crafted-comfort',
                'on_hand' => 1,
            ],
            [
                'sku' => 'CRAFT-BAR-001',
                'name' => 'Compact Bar Cabinet',
                'price_tagged' => 15400.00,
                'status' => 'archived',
                'category_slug' => 'bar-cabinets',
                'brand_slug' => 'crafted-comfort',
                'on_hand' => 1,
            ],
            [
                'sku' => 'NORD-LIGHT-001',
                'name' => 'Nordic Pendant Light',
                'price_tagged' => 6400.00,
                'status' => 'active',
                'category_slug' => 'ceiling-lights',
                'brand_slug' => 'nordic-lights',
                'on_hand' => 1,
            ],
            [
                'sku' => 'NORD-LAMP-001',
                'name' => 'Scandi Table Lamp',
                'price_tagged' => 3200.00,
                'status' => 'draft',
                'category_slug' => 'table-lamps',
                'brand_slug' => 'nordic-lights',
                'on_hand' => 10,
            ],
            [
                'sku' => 'LIGHT-FLOOR-001',
                'name' => 'Minimalist Floor Lamp',
                'price_tagged' => 5200.00,
                'status' => 'active',
                'category_slug' => 'floor-lamps',
                'brand_slug' => 'nordic-lights',
                'on_hand' => 2,
            ],
            [
                'sku' => 'URBN-PATIO-001',
                'name' => 'Urban Outdoor Lounge',
                'price_tagged' => 42900.00,
                'status' => 'archived',
                'category_slug' => 'patio-sets',
                'brand_slug' => 'urban-retreat',
                'on_hand' => 1,
            ],
            [
                'sku' => 'URBN-GARDEN-001',
                'name' => 'Garden Lantern Set',
                'price_tagged' => 8900.00,
                'status' => 'active',
                'category_slug' => 'garden-decor',
                'brand_slug' => 'urban-retreat',
                'on_hand' => 4,
            ],
            [
                'sku' => 'DECOR-MIRROR-001',
                'name' => 'Round Accent Mirror',
                'price_tagged' => 4500.00,
                'status' => 'active',
                'category_slug' => 'mirrors',
                'brand_slug' => null,
                'on_hand' => 1,
            ],
        ];

        $products = array_map(function (array $product): array {
            $product['image_url'] = $product['image_url'] ?? sprintf('https://cdn.example.com/products/%s.jpg', Str::slug($product['sku']));

            return $product;
        }, $products);

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

            $priceTagged = $product['price_tagged'];

            Product::updateOrCreate(
                ['sku' => $product['sku']],
                [
                    'name' => $product['name'],
                    'status' => $product['status'],
                    'category_id' => $category->id,
                    'brand_id' => $brandId,
                    'image_url' => $product['image_url'],
                    'on_hand' => $product['on_hand'],
                    'price_tagged' => $priceTagged,
                    'price_discounted_tag' => $priceTagged * 0.95,
                    'price_discounted_net' => $priceTagged * 0.90,
                    'price_vat' => $priceTagged * 1.07,
                    'price_vat_credit' => $priceTagged * 1.07,
                    'cost' => $priceTagged * 0.3,
                ]
            );
        }

        Product::factory()
            ->count(100)
            ->create();
    }
}
