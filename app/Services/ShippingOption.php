<?php

namespace App\Services;

final class ShippingOption
{
    public function __construct(
        public readonly string $courier,
        public readonly string $courierName,
        public readonly string $service,
        public readonly string $description,
        public readonly int $cost,
        public readonly ?string $etd,
    ) {}

    public function key(): string
    {
        return "{$this->courier}|{$this->service}";
    }

    public function courierLabel(): string
    {
        return match ($this->courier) {
            'jne' => 'JNE',
            'jnt' => 'J&T',
            'sicepat' => 'SiCepat',
            'pos' => 'POS Indonesia',
            'tiki' => 'TIKI',
            'anteraja' => 'AnterAja',
            'ninja' => 'Ninja Xpress',
            'lion' => 'Lion Parcel',
            default => $this->courierName,
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(...$data);
    }
}
