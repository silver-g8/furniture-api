<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PhotoCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class InstallationPhoto extends Model
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'installation_order_id',
        'category',
        'file_path',
        'thumbnail_path',
        'caption',
        'uploaded_by',
        'display_order',
        'uploaded_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'category' => PhotoCategory::class,
        'uploaded_at' => 'datetime',
        'display_order' => 'integer',
    ];

    /**
     * Get the installation order for this photo.
     *
     * @return BelongsTo<InstallationOrder, $this>
     */
    public function installationOrder(): BelongsTo
    {
        return $this->belongsTo(InstallationOrder::class, 'installation_order_id');
    }

    /**
     * Get the user who uploaded this photo.
     *
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get the full URL for the photo.
     */
    public function getUrl(): string
    {
        return Storage::url($this->file_path);
    }

    /**
     * Get the full URL for the thumbnail.
     */
    public function getThumbnailUrl(): string
    {
        return Storage::url($this->thumbnail_path);
    }

    /**
     * Check if this is an 'after' photo.
     */
    public function isAfterPhoto(): bool
    {
        return $this->category === PhotoCategory::After;
    }

    /**
     * Check if this is a 'before' photo.
     */
    public function isBeforePhoto(): bool
    {
        return $this->category === PhotoCategory::Before;
    }

    /**
     * Scope to filter by category.
     *
     * @param  Builder<InstallationPhoto>  $query
     * @return Builder<InstallationPhoto>
     */
    public function scopeCategory(Builder $query, PhotoCategory|string $category): Builder
    {
        if (is_string($category)) {
            $category = PhotoCategory::from($category);
        }

        return $query->where('category', $category);
    }

    /**
     * Scope to get photos ordered by display order.
     *
     * @param  Builder<InstallationPhoto>  $query
     * @return Builder<InstallationPhoto>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order', 'asc');
    }

    /**
     * Scope to get 'after' photos.
     *
     * @param  Builder<InstallationPhoto>  $query
     * @return Builder<InstallationPhoto>
     */
    public function scopeAfterPhotos(Builder $query): Builder
    {
        return $query->where('category', PhotoCategory::After);
    }

    /**
     * Scope to get 'before' photos.
     *
     * @param  Builder<InstallationPhoto>  $query
     * @return Builder<InstallationPhoto>
     */
    public function scopeBeforePhotos(Builder $query): Builder
    {
        return $query->where('category', PhotoCategory::Before);
    }
}
