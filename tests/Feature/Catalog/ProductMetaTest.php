<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
    Product::factory()->count(1)->create();
});

test('authenticated user can retrieve product meta configuration', function (): void {
    $response = $this->actingAs($this->user)->getJson('/api/v1/products/meta');

    $response
        ->assertStatus(200)
        ->assertJson(fn (AssertableJson $json) => $json
            ->has('index_fields')
            ->has('form_fields')
            ->has('show_fields')
            ->where('index_fields', fn ($fields) => in_array('sku', is_array($fields) ? $fields : $fields->all(), true))
            ->etc()
        );

    $payload = $response->json();

    expect($payload['form_fields'])->toBeArray();
    $firstFormField = $payload['form_fields'][0];
    expect($firstFormField)
        ->toHaveKeys(['key', 'label', 'component', 'rules', 'props']);
});

test('unauthenticated request is rejected for product meta', function (): void {
    $response = $this->getJson('/api/v1/products/meta');

    $response->assertStatus(401);
});
