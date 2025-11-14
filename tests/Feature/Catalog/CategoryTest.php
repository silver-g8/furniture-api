<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('can list categories', function () {
    Category::factory()->count(3)->create();

    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/categories');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'slug', 'parentId', 'isActive', 'createdAt', 'updatedAt'],
            ],
            'meta',
        ]);
});

test('can show a category', function () {
    $category = Category::factory()->create();

    $response = $this->actingAs($this->user)
        ->getJson("/api/v1/categories/{$category->id}");

    $response->assertStatus(200)
        ->assertJson([
            'data' => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ],
        ])
        ->assertJsonStructure([
            'data' => ['id', 'name', 'slug', 'parentId', 'isActive', 'createdAt', 'updatedAt'],
        ]);
});

test('can create a category', function () {
    $data = [
        'name' => 'Test Category',
        'slug' => 'test-category',
        'is_active' => true,
    ];

    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/categories', $data);

    $response->assertStatus(201)
        ->assertJson([
            'data' => [
                'name' => 'Test Category',
                'slug' => 'test-category',
            ],
        ])
        ->assertJsonStructure([
            'data' => ['id', 'name', 'slug', 'parentId', 'isActive', 'createdAt', 'updatedAt'],
        ]);

    $this->assertDatabaseHas('categories', [
        'name' => 'Test Category',
        'slug' => 'test-category',
    ]);
});

test('can update a category', function () {
    $category = Category::factory()->create();

    $data = [
        'name' => 'Updated Category',
        'slug' => 'updated-category',
    ];

    $response = $this->actingAs($this->user)
        ->putJson("/api/v1/categories/{$category->id}", $data);

    $response->assertStatus(200)
        ->assertJson([
            'data' => [
                'name' => 'Updated Category',
                'slug' => 'updated-category',
            ],
        ])
        ->assertJsonStructure([
            'data' => ['id', 'name', 'slug', 'parentId', 'isActive', 'createdAt', 'updatedAt'],
        ]);

    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'name' => 'Updated Category',
        'slug' => 'updated-category',
    ]);
});

test('can delete a category', function () {
    $category = Category::factory()->create();

    $response = $this->actingAs($this->user)
        ->deleteJson("/api/v1/categories/{$category->id}");

    $response->assertStatus(204);

    $this->assertDatabaseMissing('categories', [
        'id' => $category->id,
    ]);
});

test('cannot create category with duplicate name', function () {
    Category::factory()->create(['name' => 'Duplicate Category']);

    $data = [
        'name' => 'Duplicate Category',
        'slug' => 'duplicate-category-2',
    ];

    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/categories', $data);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

test('cannot create category with duplicate slug', function () {
    Category::factory()->create(['slug' => 'duplicate-slug']);

    $data = [
        'name' => 'Another Category',
        'slug' => 'duplicate-slug',
    ];

    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/categories', $data);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['slug']);
});

test('requires authentication to access categories', function () {
    $response = $this->getJson('/api/v1/categories');

    $response->assertStatus(401);
});

test('can create category with parent', function () {
    $parent = Category::factory()->create();

    $data = [
        'name' => 'Child Category',
        'slug' => 'child-category',
        'parent_id' => $parent->id,
        'is_active' => true,
    ];

    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/categories', $data);

    $response->assertStatus(201)
        ->assertJson([
            'data' => [
                'name' => 'Child Category',
                'parentId' => $parent->id,
            ],
        ]);
});

test('validates parent_id exists', function () {
    $data = [
        'name' => 'Child Category',
        'slug' => 'child-category',
        'parent_id' => 99999,
    ];

    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/categories', $data);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['parent_id']);
});

test('auto generates slug from name if not provided', function () {
    $data = [
        'name' => 'Auto Slug Category',
        'is_active' => true,
    ];

    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/categories', $data);

    $response->assertStatus(201)
        ->assertJson([
            'data' => [
                'name' => 'Auto Slug Category',
                'slug' => 'auto-slug-category',
            ],
        ])
        ->assertJsonStructure([
            'data' => ['id', 'name', 'slug', 'parentId', 'isActive', 'createdAt', 'updatedAt'],
        ]);
});

// Tree View Tests
test('can get category tree view', function () {
    $parent = Category::factory()->create(['name' => 'Parent', 'slug' => 'parent']);
    $child = Category::factory()->create(['name' => 'Child', 'slug' => 'child', 'parent_id' => $parent->id]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/categories?tree=1');

    $response->assertStatus(200)
        ->assertJsonStructure([
            '*' => ['id', 'name', 'slug', 'parentId', 'isActive', 'children'],
        ])
        ->assertJsonCount(1) // Should have one root node
        ->assertJsonFragment([
            'id' => $parent->id,
            'name' => 'Parent',
            'slug' => 'parent',
            'parentId' => null,
        ]);
});

test('tree view with multiple levels works correctly', function () {
    $root = Category::factory()->create(['name' => 'Root', 'slug' => 'root']);
    $level1 = Category::factory()->create(['name' => 'Level 1', 'slug' => 'level-1', 'parent_id' => $root->id]);
    $level2 = Category::factory()->create(['name' => 'Level 2', 'slug' => 'level-2', 'parent_id' => $level1->id]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/categories?tree=1');

    $response->assertStatus(200)
        ->assertJsonCount(1); // One root

    $data = $response->json();
    expect($data[0]['children'])->toHaveCount(1)
        ->and($data[0]['children'][0]['children'])->toHaveCount(1);
});

test('tree view respects is_active filter', function () {
    $active = Category::factory()->create(['name' => 'Active', 'slug' => 'active', 'is_active' => true]);
    $inactive = Category::factory()->create(['name' => 'Inactive', 'slug' => 'inactive', 'is_active' => false]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/categories?tree=1&is_active=true');

    $response->assertStatus(200);
    $data = $response->json();
    expect($data)->toHaveCount(1)
        ->and($data[0]['id'])->toBe($active->id);
});

// Filtering Tests
test('can filter categories by parent_id', function () {
    $parent = Category::factory()->create();
    $child1 = Category::factory()->create(['parent_id' => $parent->id]);
    $child2 = Category::factory()->create(['parent_id' => $parent->id]);
    Category::factory()->create(); // Different parent

    $response = $this->actingAs($this->user)
        ->getJson("/api/v1/categories?parent_id={$parent->id}");

    $response->assertStatus(200)
        ->assertJsonCount(2, 'data'); // Should have 2 children
});

test('can filter categories by is_active', function () {
    Category::factory()->count(3)->create(['is_active' => true]);
    Category::factory()->count(2)->create(['is_active' => false]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/categories?is_active=true');

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data');
});

test('can use combined filters', function () {
    $parent = Category::factory()->create(['is_active' => true]);
    $activeChild = Category::factory()->create(['parent_id' => $parent->id, 'is_active' => true]);
    Category::factory()->create(['parent_id' => $parent->id, 'is_active' => false]);

    $response = $this->actingAs($this->user)
        ->getJson("/api/v1/categories?parent_id={$parent->id}&is_active=true");

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonFragment(['id' => $activeChild->id]);
});

test('per_page parameter works correctly', function () {
    Category::factory()->count(25)->create();

    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/categories?per_page=10');

    $response->assertStatus(200)
        ->assertJsonCount(10, 'data')
        ->assertJsonPath('meta.per_page', 10);
});

// Delete Validation Tests
test('cannot delete category with children', function () {
    $parent = Category::factory()->create();
    Category::factory()->create(['parent_id' => $parent->id]);

    $response = $this->actingAs($this->user)
        ->deleteJson("/api/v1/categories/{$parent->id}");

    $response->assertStatus(422)
        ->assertJson(['message' => 'Cannot delete category with child categories.']);

    $this->assertDatabaseHas('categories', ['id' => $parent->id]);
});

test('cannot delete category with products', function () {
    $category = Category::factory()->create();

    // Create a product associated with this category
    \App\Models\Product::factory()->create(['category_id' => $category->id]);

    $response = $this->actingAs($this->user)
        ->deleteJson("/api/v1/categories/{$category->id}");

    $response->assertStatus(422)
        ->assertJson(['message' => 'Cannot delete category that has products.']);

    $this->assertDatabaseHas('categories', ['id' => $category->id]);
});

test('can delete category with no children and no products', function () {
    $category = Category::factory()->create();

    $response = $this->actingAs($this->user)
        ->deleteJson("/api/v1/categories/{$category->id}");

    $response->assertStatus(204);

    $this->assertDatabaseMissing('categories', ['id' => $category->id]);
});

test('delete error message format is correct', function () {
    $parent = Category::factory()->create();
    Category::factory()->create(['parent_id' => $parent->id]);

    $response = $this->actingAs($this->user)
        ->deleteJson("/api/v1/categories/{$parent->id}");

    $response->assertStatus(422)
        ->assertJsonStructure(['message'])
        ->assertJsonMissing(['errors']); // Should not have validation errors structure
});

// Self-Parent Prevention Tests
test('cannot set parent_id to self in update', function () {
    $category = Category::factory()->create();

    $response = $this->actingAs($this->user)
        ->putJson("/api/v1/categories/{$category->id}", [
            'parent_id' => $category->id,
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['parent_id']);
});

test('can set parent_id to different category in update', function () {
    $parent = Category::factory()->create();
    $child = Category::factory()->create();

    $response = $this->actingAs($this->user)
        ->putJson("/api/v1/categories/{$child->id}", [
            'parent_id' => $parent->id,
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.parentId', $parent->id);

    $this->assertDatabaseHas('categories', [
        'id' => $child->id,
        'parent_id' => $parent->id,
    ]);
});

// Response Format Tests
test('response format uses camelCase', function () {
    $category = Category::factory()->create();

    $response = $this->actingAs($this->user)
        ->getJson("/api/v1/categories/{$category->id}");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'slug',
                'parentId',
                'isActive',
                'createdAt',
                'updatedAt',
            ],
        ])
        ->assertJsonMissing([
            'data' => ['parent_id', 'is_active', 'created_at', 'updated_at'],
        ]);
});

test('tree view response format matches CategoryNode structure', function () {
    $parent = Category::factory()->create();
    $child = Category::factory()->create(['parent_id' => $parent->id]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/categories?tree=1');

    $response->assertStatus(200)
        ->assertJsonStructure([
            '*' => [
                'id',
                'name',
                'slug',
                'parentId',
                'isActive',
                'children' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'parentId',
                        'isActive',
                        'children',
                    ],
                ],
            ],
        ]);
});
