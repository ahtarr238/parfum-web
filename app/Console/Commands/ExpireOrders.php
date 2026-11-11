<?php

namespace App\Console\Commands;

use App\Services\OrderService;
use Illuminate\Console\Command;

class ExpireOrders extends Command
{
    protected $signature = 'orders:expire';

    protected $description = 'Batalkan order Menunggu Bayar yang lewat 24 jam dan kembalikan stoknya';

    public function handle(OrderService $orders): int
    {
        $count = $orders->expireOverdue();

        $this->info("{$count} pesanan kedaluwarsa dibatalkan.");

        return self::SUCCESS;
    }
}
