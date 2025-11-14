<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->category = Category::factory()->create();
    Storage::fake('public');
});

test('can upload image successfully', function () {
    $product = Product::factory()->create(['category_id' => $this->category->id]);

    $file = UploadedFile::fake()->image('photo.jpg', 800, 600);

    $response = $this->actingAs($this->user)
        ->postJson("/api/v1/products/{$product->id}/image", [
            'image' => $file,
        ]);

    $response->assertStatus(200)
        ->assertJsonStructure(['image_url']);

    // Assert file exists in storage
    Storage::disk('public')->assertExists("products/{$product->id}/{$file->hashName()}");

    // Assert database image_url is updated
    $product->refresh();
    $this->assertNotNull($product->image_url);
    $this->assertStringContainsString("products/{$product->id}/", $product->image_url);
});

test('rejects invalid mime types', function () {
    $product = Product::factory()->create(['category_id' => $this->category->id]);

    $file = UploadedFile::fake()->create('document.txt', 100);

    $response = $this->actingAs($this->user)
        ->postJson("/api/v1/products/{$product->id}/image", [
            'image' => $file,
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['image']);
});

test('rejects files over size limit', function () {
    $product = Product::factory()->create(['category_id' => $this->category->id]);

    // Create a file larger than 2048 KB (2MB)
    $file = UploadedFile::fake()->image('large.jpg')->size(3000); // 3MB

    $response = $this->actingAs($this->user)
        ->postJson("/api/v1/products/{$product->id}/image", [
            'image' => $file,
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['image']);
});

test('replaces existing image', function () {
    $product = Product::factory()->create([
        'category_id' => $this->category->id,
        'image_url' => '/storage/products/1/old-image.jpg',
    ]);

    // Create old file in storage
    $oldFile = 'products/1/old-image.jpg';
    Storage::disk('public')->put($oldFile, 'fake content');

    $newFile = UploadedFile::fake()->image('new-photo.jpg');

    $response = $this->actingAs($this->user)
        ->postJson("/api/v1/products/{$product->id}/image", [
            'image' => $newFile,
        ]);

    $response->assertStatus(200);

    // Assert old file is deleted
    Storage::disk('public')->assertMissing($oldFile);

    // Assert new file exists
    Storage::disk('public')->assertExists("products/{$product->id}/{$newFile->hashName()}");

    // Assert image_url is updated
    $product->refresh();
    $this->assertNotEquals('/storage/products/1/old-image.jpg', $product->image_url);
    $this->assertStringContainsString("products/{$product->id}/", $product->image_url);
});

test('can delete image successfully', function () {
    $product = Product::factory()->create([
        'category_id' => $this->category->id,
        'image_url' => '/storage/products/1/test-image.jpg',
    ]);

    // Create file in storage
    $filePath = 'products/1/test-image.jpg';
    Storage::disk('public')->put($filePath, 'fake content');

    $response = $this->actingAs($this->user)
        ->deleteJson("/api/v1/products/{$product->id}/image");

    $response->assertStatus(204);

    // Assert image_url is null in database
    $product->refresh();
    $this->assertNull($product->image_url);

    // Assert file is deleted from storage
    Storage::disk('public')->assertMissing($filePath);
});

test('can delete when no image exists', function () {
    $product = Product::factory()->create([
        'category_id' => $this->category->id,
        'image_url' => null,
    ]);

    $response = $this->actingAs($this->user)
        ->deleteJson("/api/v1/products/{$product->id}/image");

    // Should not error, just return success
    $response->assertStatus(204);

    // Assert image_url remains null
    $product->refresh();
    $this->assertNull($product->image_url);
});
