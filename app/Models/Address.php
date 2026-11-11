<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'label', 'recipient', 'phone',
        'province_id', 'province_name', 'city_id', 'city_name', 'district_id', 'district_name',
        'detail', 'postal_code', 'is_default',
    ];

    protected function casts(): array
    {
        return [
            'province_id' => 'integer',
            'city_id' => 'integer',
            'district_id' => 'integer',
            'is_default' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fullText(): string
    {
        return "{$this->detail}, {$this->district_name}, {$this->city_name}, {$this->province_name} {$this->postal_code}";
    }

    /**
     * Data alamat yang disalin ke order (snapshot).
     *
     * @return array<string, mixed>
     */
    public function toSnapshot(): array
    {
        return $this->only([
            'label', 'recipient', 'phone',
            'province_id', 'province_name', 'city_id', 'city_name', 'district_id', 'district_name',
            'detail', 'postal_code',
        ]);
    }
}
