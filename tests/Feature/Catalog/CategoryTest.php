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
                '*' => ['id', 'name', 'slug', 'parent_id', 'is_active', 'created_at', 'updated_at'],
            ],
        ]);
});

test('can show a category', function () {
    $category = Category::factory()->create();

    $response = $this->actingAs($this->user)
        ->getJson("/api/v1/categories/{$category->id}");

    $response->assertStatus(200)
        ->assertJson([
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
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
            'name' => 'Test Category',
            'slug' => 'test-category',
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
            'name' => 'Updated Category',
            'slug' => 'updated-category',
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
            'name' => 'Child Category',
            'parent_id' => $parent->id,
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
            'name' => 'Auto Slug Category',
            'slug' => 'auto-slug-category',
        ]);
});
