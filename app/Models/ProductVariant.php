<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'size_ml', 'is_decant', 'sku', 'price', 'stock', 'weight_gram'];

    protected function casts(): array
    {
        return [
            'size_ml' => 'integer',
            'is_decant' => 'boolean',
            'price' => 'integer',
            'stock' => 'integer',
            'weight_gram' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function label(): string
    {
        return $this->is_decant ? "Decant {$this->size_ml}ml" : "{$this->size_ml}ml";
    }
}
