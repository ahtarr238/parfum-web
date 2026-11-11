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
}
