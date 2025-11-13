<?php

declare(strict_types=1);

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->category = Category::factory()->create();
    $this->brand = Brand::factory()->create();
});

test('can assign a brand when creating a product', function (): void {
    $payload = [
        'category_id' => $this->category->id,
        'brand_id' => $this->brand->id,
        'name' => 'Brand Linked Product',
        'sku' => 'BRAND-001',
        'price' => 4990,
        'status' => 'active',
        'on_hand' => 12,
    ];

    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/products', $payload);

    $response->assertStatus(201)
        ->assertJsonPath('brand_id', $this->brand->id)
        ->assertJsonPath('on_hand', 12);

    $this->assertDatabaseHas('products', [
        'sku' => 'BRAND-001',
        'brand_id' => $this->brand->id,
        'on_hand' => 12,
    ]);
});

test('can update product brand', function (): void {
    $product = Product::factory()->create([
        'category_id' => $this->category->id,
        'brand_id' => $this->brand->id,
    ]);

    $newBrand = Brand::factory()->create();

    $response = $this->actingAs($this->user)
        ->putJson("/api/v1/products/{$product->id}", [
            'brand_id' => $newBrand->id,
        ]);

    $response->assertStatus(200)
        ->assertJsonPath('brand_id', $newBrand->id);

    $this->assertDatabaseHas('products', [
        'id' => $product->id,
        'brand_id' => $newBrand->id,
    ]);
});

test('validates brand exists when assigning', function (): void {
    $payload = [
        'category_id' => $this->category->id,
        'brand_id' => 999999,
        'name' => 'Invalid Brand Product',
        'sku' => 'BRAND-999',
        'price' => 2500,
        'status' => 'active',
        'on_hand' => 8,
    ];

    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/products', $payload);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['brand_id']);
});
