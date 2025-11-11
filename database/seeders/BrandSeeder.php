<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $brands = [
            [
                'name' => 'Alpha Furniture',
                'slug' => 'alpha-furniture',
                'code' => 'ALPHA',
                'logo_path' => 'brands/alpha-furniture.png',
                'website_url' => 'https://alpha-furniture.example.com',
                'is_active' => true,
                'meta' => [
                    'origin_country' => 'TH',
                    'established' => 2010,
                    'specialty' => 'Modular sofas and sectionals',
                ],
            ],
            [
                'name' => 'Beta Living',
                'slug' => 'beta-living',
                'code' => 'BETA',
                'logo_path' => 'brands/beta-living.png',
                'website_url' => 'https://beta-living.example.com',
                'is_active' => true,
                'meta' => [
                    'origin_country' => 'TH',
                    'established' => 2014,
                    'specialty' => 'Contemporary bedroom sets',
                ],
            ],
            [
                'name' => 'Crafted Comfort',
                'slug' => 'crafted-comfort',
                'code' => 'CRAFT',
                'logo_path' => 'brands/crafted-comfort.png',
                'website_url' => 'https://crafted-comfort.example.com',
                'is_active' => true,
                'meta' => [
                    'origin_country' => 'JP',
                    'established' => 2005,
                    'specialty' => 'Solid wood dining collections',
                ],
            ],
            [
                'name' => 'Nordic Lights',
                'slug' => 'nordic-lights',
                'code' => 'NORD',
                'logo_path' => 'brands/nordic-lights.png',
                'website_url' => 'https://nordic-lights.example.com',
                'is_active' => true,
                'meta' => [
                    'origin_country' => 'SE',
                    'established' => 2012,
                    'specialty' => 'LED lighting solutions',
                ],
            ],
            [
                'name' => 'Urban Retreat',
                'slug' => 'urban-retreat',
                'code' => 'URBN',
                'logo_path' => 'brands/urban-retreat.png',
                'website_url' => 'https://urban-retreat.example.com',
                'is_active' => false,
                'meta' => [
                    'origin_country' => 'US',
                    'established' => 2018,
                    'specialty' => 'Premium outdoor lounges',
                ],
            ],
        ];

        foreach ($brands as $brand) {
            Brand::updateOrCreate(
                ['slug' => $brand['slug']],
                [
                    'name' => $brand['name'],
                    'code' => $brand['code'],
                    'logo_path' => $brand['logo_path'],
                    'website_url' => $brand['website_url'],
                    'is_active' => $brand['is_active'],
                    'meta' => $brand['meta'],
                ]
            );
        }
    }
}
