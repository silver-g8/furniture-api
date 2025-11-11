<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Furniture',
                'slug' => 'furniture',
                'is_active' => true,
                'children' => [
                    [
                        'name' => 'Living Room',
                        'slug' => 'living-room',
                        'is_active' => true,
                        'children' => [
                            [
                                'name' => 'Sofas',
                                'slug' => 'sofas',
                                'is_active' => true,
                            ],
                            [
                                'name' => 'Coffee Tables',
                                'slug' => 'coffee-tables',
                                'is_active' => true,
                            ],
                            [
                                'name' => 'TV Stands',
                                'slug' => 'tv-stands',
                                'is_active' => false,
                            ],
                        ],
                    ],
                    [
                        'name' => 'Bedroom',
                        'slug' => 'bedroom',
                        'is_active' => true,
                        'children' => [
                            [
                                'name' => 'Beds',
                                'slug' => 'beds',
                                'is_active' => true,
                            ],
                            [
                                'name' => 'Wardrobes',
                                'slug' => 'wardrobes',
                                'is_active' => true,
                            ],
                        ],
                    ],
                    [
                        'name' => 'Dining Room',
                        'slug' => 'dining-room',
                        'is_active' => true,
                        'children' => [
                            [
                                'name' => 'Dining Sets',
                                'slug' => 'dining-sets',
                                'is_active' => true,
                            ],
                            [
                                'name' => 'Bar Cabinets',
                                'slug' => 'bar-cabinets',
                                'is_active' => false,
                            ],
                        ],
                    ],
                ],
            ],
            [
                'name' => 'Home Decor',
                'slug' => 'home-decor',
                'is_active' => true,
                'children' => [
                    [
                        'name' => 'Wall Art',
                        'slug' => 'wall-art',
                        'is_active' => true,
                    ],
                    [
                        'name' => 'Rugs & Carpets',
                        'slug' => 'rugs-carpets',
                        'is_active' => true,
                    ],
                    [
                        'name' => 'Mirrors',
                        'slug' => 'mirrors',
                        'is_active' => true,
                    ],
                ],
            ],
            [
                'name' => 'Lighting',
                'slug' => 'lighting',
                'is_active' => true,
                'children' => [
                    [
                        'name' => 'Ceiling Lights',
                        'slug' => 'ceiling-lights',
                        'is_active' => true,
                    ],
                    [
                        'name' => 'Table Lamps',
                        'slug' => 'table-lamps',
                        'is_active' => true,
                    ],
                    [
                        'name' => 'Floor Lamps',
                        'slug' => 'floor-lamps',
                        'is_active' => false,
                    ],
                ],
            ],
            [
                'name' => 'Outdoor',
                'slug' => 'outdoor',
                'is_active' => false,
                'children' => [
                    [
                        'name' => 'Patio Sets',
                        'slug' => 'patio-sets',
                        'is_active' => false,
                    ],
                    [
                        'name' => 'Garden Decor',
                        'slug' => 'garden-decor',
                        'is_active' => false,
                    ],
                ],
            ],
        ];

        foreach ($categories as $category) {
            $this->createCategoryWithChildren($category);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function createCategoryWithChildren(array $data, ?Category $parent = null): Category
    {
        $category = Category::updateOrCreate(
            ['slug' => $data['slug']],
            [
                'name' => $data['name'],
                'parent_id' => $parent?->id,
                'is_active' => $data['is_active'],
            ]
        );

        $children = $data['children'] ?? [];

        foreach ($children as $child) {
            $this->createCategoryWithChildren($child, $category);
        }

        return $category;
    }
}
