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
});

test('filters products by brand_id', function (): void {
    $brandA = Brand::factory()->create(['name' => 'Alpha', 'slug' => 'alpha-'.strtolower(str()->random(4))]);
    $brandB = Brand::factory()->create(['name' => 'Beta', 'slug' => 'beta-'.strtolower(str()->random(4))]);

    $productA = Product::factory()->create([
        'category_id' => $this->category->id,
        'brand_id' => $brandA->id,
    ]);
    $productB = Product::factory()->create([
        'category_id' => $this->category->id,
        'brand_id' => $brandB->id,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/products?brand_id='.$brandA->id);

    $response->assertStatus(200)
        ->assertJsonFragment(['id' => $productA->id])
        ->assertJsonMissing(['id' => $productB->id]);

    collect($response->json('data'))
        ->each(fn (array $item) => expect($item['brand_id'])->toBe($brandA->id));
});

test('returns validation error when brand_id is invalid', function (): void {
    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/products?brand_id=999999');

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['brand_id']);
});
