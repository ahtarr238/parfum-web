<?php

namespace App\Models;

use App\Enums\ScentFamily;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id', 'brand_id', 'name', 'slug', 'description', 'scent_family',
        'top_notes', 'middle_notes', 'base_notes', 'images', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'scent_family' => ScentFamily::class,
            'images' => 'array',
            'is_active' => 'boolean',
            'min_price' => 'integer',
            'total_stock' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('size_ml');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function visibleReviews(): HasMany
    {
        return $this->reviews()->where('is_visible', true)->latest();
    }

    /**
     * Habis jika produk nonaktif atau total stok semua varian 0.
     */
    public function isSoldOut(): bool
    {
        if (! $this->is_active) {
            return true;
        }

        $stock = array_key_exists('total_stock', $this->attributes)
            ? (int) $this->attributes['total_stock']
            : (int) $this->variants()->sum('stock');

        return $stock <= 0;
    }

    public function startingPrice(): int
    {
        return array_key_exists('min_price', $this->attributes)
            ? (int) $this->attributes['min_price']
            : (int) $this->variants()->min('price');
    }

    public function coverImage(): ?string
    {
        return $this->images[0] ?? null;
    }

    /**
     * @return list<string>
     */
    public function notesList(string $layer): array
    {
        return collect(explode(',', (string) $this->{$layer.'_notes'}))
            ->map(fn (string $n) => trim($n))
            ->filter()
            ->values()
            ->all();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
