<?php

namespace App\Enums;

enum ScentFamily: string
{
    case Woody = 'woody';
    case Floral = 'floral';
    case Fresh = 'fresh';
    case Oriental = 'oriental';
    case Gourmand = 'gourmand';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $s) => [$s->value => $s->label()])->all();
    }
}
