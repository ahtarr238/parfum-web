<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Enums\OrderStatus;
use App\Filament\Resources\OrderResource;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    public function getTabs(): array
    {
        $status = fn (OrderStatus ...$s) => fn (Builder $query) => $query->whereIn('status', $s);

        return [
            'semua' => Tab::make('Semua'),
            'perlu_diproses' => Tab::make('Perlu diproses')->modifyQueryUsing($status(OrderStatus::Paid, OrderStatus::Processing)),
            'menunggu_bayar' => Tab::make('Menunggu bayar')->modifyQueryUsing($status(OrderStatus::PendingPayment)),
            'dikirim' => Tab::make('Dikirim')->modifyQueryUsing($status(OrderStatus::Shipped)),
            'selesai' => Tab::make('Selesai')->modifyQueryUsing($status(OrderStatus::Completed)),
            'dibatalkan' => Tab::make('Dibatalkan')->modifyQueryUsing($status(OrderStatus::Cancelled)),
        ];
    }
}
