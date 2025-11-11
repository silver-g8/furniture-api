<?php

declare(strict_types=1);

use App\Models\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
});

test('can list brands', function (): void {
    Brand::factory()->count(3)->create();

    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/brands');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'slug', 'code', 'is_active', 'meta', 'created_at', 'updated_at'],
            ],
        ]);
});

test('can create a brand', function (): void {
    $payload = Brand::factory()->make()->toArray();
    $payload['slug'] = Str::slug($payload['name']).'-test';

    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/brands', $payload);

    $response->assertStatus(201)
        ->assertJsonPath('data.name', $payload['name'])
        ->assertJsonPath('data.slug', $payload['slug']);

    $this->assertDatabaseHas('brands', [
        'name' => $payload['name'],
        'slug' => $payload['slug'],
    ]);
});

test('can update a brand', function (): void {
    $brand = Brand::factory()->create();

    $payload = [
        'name' => $brand->name.' Updated',
        'slug' => $brand->slug.'-updated',
        'is_active' => ! $brand->is_active,
    ];

    $response = $this->actingAs($this->user)
        ->putJson("/api/v1/brands/{$brand->id}", $payload);

    $response->assertStatus(200)
        ->assertJsonPath('data.name', $payload['name'])
        ->assertJsonPath('data.slug', $payload['slug'])
        ->assertJsonPath('data.is_active', $payload['is_active']);

    $this->assertDatabaseHas('brands', [
        'id' => $brand->id,
        'name' => $payload['name'],
        'slug' => $payload['slug'],
        'is_active' => $payload['is_active'],
    ]);
});

test('can delete a brand', function (): void {
    $brand = Brand::factory()->create();

    $response = $this->actingAs($this->user)
        ->deleteJson("/api/v1/brands/{$brand->id}");

    $response->assertStatus(204);

    $this->assertDatabaseMissing('brands', [
        'id' => $brand->id,
    ]);
});

test('validates unique constraints when creating brand', function (): void {
    $brand = Brand::factory()->create();

    $payload = [
        'name' => $brand->name,
        'slug' => $brand->slug,
        'code' => $brand->code,
    ];

    $response = $this->actingAs($this->user)
        ->postJson('/api/v1/brands', $payload);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name', 'slug']);
});

test('requires authentication to manage brands', function (): void {
    $response = $this->getJson('/api/v1/brands');

    $response->assertStatus(401);
});
