<?php

declare(strict_types=1);

use App\Models\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
});

test('authenticated user can retrieve brand options', function (): void {
    $names = ['Acacia Works', 'Bamboo Craft', 'Zen Living'];
    foreach ($names as $name) {
        Brand::factory()->create(['name' => $name]);
    }

    $response = $this->actingAs($this->user)
        ->getJson('/api/v1/brands/options');

    $response->assertStatus(200)
        ->assertJsonCount(3)
        ->assertJson(fn (AssertableJson $json) => $json->each(
            fn (AssertableJson $item) => $item
                ->whereType('id', 'integer')
                ->whereType('name', 'string')
        ));

    $ordered = $response->json();
    $sorted = $ordered;
    usort($sorted, fn (array $a, array $b) => $a['name'] <=> $b['name']);
    expect($ordered)->toBe($sorted);
});

test('unauthenticated request is rejected for brand options', function (): void {
    $response = $this->getJson('/api/v1/brands/options');

    $response->assertStatus(401);
});
