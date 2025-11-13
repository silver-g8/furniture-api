<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->category = Category::factory()->create();
});

test('can list products', function () {
    Product::factory()->count(3)->create(['category_id' => $this->category->id]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/products');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'category_id', 'name', 'sku', 'price', 'status', 'on_hand', 'created_at', 'updated_at'],
            ],
        ]);
});

test('can show a product', function () {
    $product = Product::factory()->create(['category_id' => $this->category->id]);

    $response = $this->actingAs($this->user)
        ->getJson("/api/v1/products/{$product->id}");

    $response->assertStatus(200)
        ->assertJson([
            'id' => $product->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'price' => (string) $product->price,
            'on_hand' => $product->on_hand,
        ]);
});

test('can create a product', function () {
    $data = [
        'category_id' => $this->category->id,
        'name' => 'Test Product',
        'sku' => 'TEST-001',
        'price' => 999.99,
        'status' => 'active',
        'on_hand' => 10,
    ];

    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/products', $data);

    $response->assertStatus(201)
        ->assertJson([
            'name' => 'Test Product',
            'sku' => 'TEST-001',
            'price' => '999.99',
            'status' => 'active',
            'on_hand' => 10,
        ]);

    $this->assertDatabaseHas('products', [
        'name' => 'Test Product',
        'sku' => 'TEST-001',
        'on_hand' => 10,
    ]);
});

test('can update a product', function () {
    $product = Product::factory()->create(['category_id' => $this->category->id]);

    $data = [
        'name' => 'Updated Product',
        'price' => 1999.99,
        'on_hand' => 25,
    ];

    $response = $this->actingAs($this->user)
        ->putJson("/api/v1/products/{$product->id}", $data);

    $response->assertStatus(200)
        ->assertJson([
            'name' => 'Updated Product',
            'price' => '1999.99',
            'on_hand' => 25,
        ]);

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'name' => 'Updated Product',
        'on_hand' => 25,
    ]);
});

test('can delete a product', function () {
    $product = Product::factory()->create(['category_id' => $this->category->id]);

    $response = $this->actingAs($this->user)
        ->deleteJson("/api/v1/products/{$product->id}");

    $response->assertStatus(204);

    $this->assertDatabaseMissing('products', [
        'id' => $product->id,
    ]);
});

test('cannot create product with duplicate sku', function () {
    Product::factory()->create([
        'category_id' => $this->category->id,
        'sku' => 'DUPLICATE-SKU',
    ]);

    $data = [
        'category_id' => $this->category->id,
        'name' => 'Another Product',
        'sku' => 'DUPLICATE-SKU',
        'price' => 999.99,
        'status' => 'active',
        'on_hand' => 5,
    ];

    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/products', $data);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['sku']);
});

test('validates price is not negative', function () {
    $data = [
        'category_id' => $this->category->id,
        'name' => 'Test Product',
        'sku' => 'TEST-001',
        'price' => -100,
        'status' => 'active',
        'on_hand' => 5,
    ];

    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/products', $data);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['price']);
});

test('validates status is valid enum value', function () {
    $data = [
        'category_id' => $this->category->id,
        'name' => 'Test Product',
        'sku' => 'TEST-001',
        'price' => 999.99,
        'status' => 'invalid-status',
        'on_hand' => 5,
    ];

    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/products', $data);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['status']);
});

test('validates category_id exists', function () {
    $data = [
        'category_id' => 99999,
        'name' => 'Test Product',
        'sku' => 'TEST-001',
        'price' => 999.99,
        'status' => 'active',
        'on_hand' => 5,
    ];

    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/products', $data);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['category_id']);
});

test('requires authentication to access products', function () {
    $response = $this->getJson('/api/v1/products');

    $response->assertStatus(401);
});

test('can create product with draft status', function () {
    $data = [
        'category_id' => $this->category->id,
        'name' => 'Draft Product',
        'sku' => 'DRAFT-001',
        'price' => 999.99,
        'status' => 'draft',
        'on_hand' => 0,
    ];

    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/products', $data);

    $response->assertStatus(201)
        ->assertJson([
            'status' => 'draft',
            'on_hand' => 0,
        ]);
});

test('can create product with archived status', function () {
    $data = [
        'category_id' => $this->category->id,
        'name' => 'Archived Product',
        'sku' => 'ARCH-001',
        'price' => 999.99,
        'status' => 'archived',
        'on_hand' => 3,
    ];

    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/products', $data);

    $response->assertStatus(201)
        ->assertJson([
            'status' => 'archived',
            'on_hand' => 3,
        ]);
});

test('product includes category relationship', function () {
    $product = Product::factory()->create(['category_id' => $this->category->id]);

    $response = $this->actingAs($this->user)
        ->getJson("/api/v1/products/{$product->id}");

    $response->assertStatus(200)
        ->assertJsonStructure([
            'id',
            'name',
            'category' => ['id', 'name', 'slug'],
        ]);
});

test('validates required fields on create', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/products', []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['category_id', 'name', 'sku', 'price', 'status', 'on_hand']);
});

test('can patch on_hand without affecting other fields', function () {
    $product = Product::factory()->create([
        'category_id' => $this->category->id,
        'on_hand' => 7,
    ]);

    $response = $this->actingAs($this->user)
        ->patchJson("/api/v1/products/{$product->id}", [
            'on_hand' => 42,
        ]);

    $response->assertStatus(200)
        ->assertJson([
            'id' => $product->id,
            'on_hand' => 42,
            'name' => $product->name,
        ]);

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'on_hand' => 42,
        'name' => $product->name,
    ]);
});
