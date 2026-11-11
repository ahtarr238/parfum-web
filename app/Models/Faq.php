<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    use HasFactory;

    protected $fillable = ['question', 'keywords', 'answer', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /**
     * @return list<string>
     */
    public function keywordList(): array
    {
        return collect(explode(',', $this->keywords))
            ->map(fn (string $k) => mb_strtolower(trim($k)))
            ->filter()
            ->values()
            ->all();
    }
}
