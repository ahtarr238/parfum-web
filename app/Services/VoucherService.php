<?php

namespace App\Services;

use App\Enums\VoucherType;
use App\Exceptions\VoucherException;
use App\Models\User;
use App\Models\Voucher;

class VoucherService
{
    /**
     * Cek voucher untuk ditampilkan di checkout. Tidak mengubah kuota.
     */
    public function preview(string $code, int $subtotal): Voucher
    {
        $voucher = Voucher::where('code', $this->normalize($code))->first();

        $this->validate($voucher, $subtotal);

        return $voucher;
    }

    /**
     * Pakai voucher untuk order. Harus dipanggil di dalam DB transaction (row lock pada voucher).
     */
    public function claim(string $code, int $subtotal, User $user): Voucher
    {
        $voucher = Voucher::where('code', $this->normalize($code))->lockForUpdate()->first();

        $this->validate($voucher, $subtotal);
        $voucher->increment('used');

        return $voucher;
    }

    /**
     * Kembalikan kuota saat order dibatalkan atau kedaluwarsa.
     */
    public function release(Voucher $voucher): void
    {
        Voucher::whereKey($voucher->id)->where('used', '>', 0)->decrement('used');
    }

    public function discountFor(Voucher $voucher, int $subtotal): int
    {
        $discount = match ($voucher->type) {
            VoucherType::Fixed => $voucher->value,
            VoucherType::Percent => intdiv($subtotal * min($voucher->value, 100), 100),
        };

        return min($discount, $subtotal);
    }

    private function validate(?Voucher $voucher, int $subtotal): void
    {
        if (! $voucher) {
            throw new VoucherException('Kode voucher tidak ditemukan.');
        }

        if ($voucher->starts_at->isFuture()) {
            throw new VoucherException('Voucher ini belum berlaku.');
        }

        if ($voucher->ends_at->isPast()) {
            throw new VoucherException('Voucher ini sudah berakhir.');
        }

        if ($voucher->used >= $voucher->quota) {
            throw new VoucherException('Maaf, kuota voucher ini sudah habis.');
        }

        if ($subtotal < $voucher->min_purchase) {
            throw new VoucherException('Minimal belanja '.rupiah($voucher->min_purchase).' untuk voucher ini.');
        }
    }

    private function normalize(string $code): string
    {
        return strtoupper(trim($code));
    }
}
