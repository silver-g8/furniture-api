<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    /** @use HasFactory<\Database\Factories\CategoryFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'parent_id',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the parent category.
     *
     * @return BelongsTo<Category, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Get the child categories.
     *
     * @return HasMany<Category, $this>
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Get the products for the category.
     *
     * @return HasMany<Product, $this>
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Scope a query to only include active categories.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Category>  $query
     * @return \Illuminate\Database\Eloquent\Builder<Category>
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Build a nested tree structure from a flat collection of categories.
     *
     * @param  Collection<int, Category>  $categories
     * @return array<int, array{id: int, name: string, slug: string, parentId: int|null, isActive: bool, children: array<int, array{id: int, name: string, slug: string, parentId: int|null, isActive: bool, children: array}>}>
     *
     * @phpstan-ignore-next-line
     */
    public static function buildTree(Collection $categories): array
    {
        $grouped = $categories->groupBy('parent_id');

        $buildNode = function ($parentId = null) use (&$buildNode, $grouped) {
            $children = $grouped->get($parentId, collect());

            return $children->map(function ($category) use ($buildNode) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'parentId' => $category->parent_id,
                    'isActive' => $category->is_active,
                    'children' => $buildNode($category->id),
                ];
            })->values()->all();
        };

        return $buildNode(null);
    }
}
