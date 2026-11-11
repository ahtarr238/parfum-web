<?php

namespace App\Filament\Widgets;

use App\Enums\ConversationStatus;
use App\Enums\OrderStatus;
use App\Models\Conversation;
use App\Models\Order;
use App\Support\StoreMetrics;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StoreStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected static ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $metrics = app(StoreMetrics::class);
        $now = now();
        $thisMonth = $metrics->revenueBetween($now->copy()->startOfMonth(), $now);
        $lastMonth = $metrics->revenueBetween($now->copy()->subMonthNoOverflow()->startOfMonth(), $now->copy()->subMonthNoOverflow());

        $change = $lastMonth > 0 ? round(($thisMonth - $lastMonth) / $lastMonth * 100) : null;

        return [
            Stat::make('Omzet bulan ini', rupiah($thisMonth))
                ->description($change === null ? 'Belum ada pembanding bulan lalu' : ($change >= 0 ? '+' : '').$change.'% dibanding periode sama bulan lalu')
                ->color($change !== null && $change < 0 ? 'danger' : 'success'),
            Stat::make('Order dibayar bulan ini', (string) $metrics->paidOrderCountBetween($now->copy()->startOfMonth(), $now)),
            Stat::make('Perlu diproses', (string) Order::whereIn('status', [OrderStatus::Paid, OrderStatus::Processing])->count())
                ->description('Dibayar atau sedang dikemas'),
            Stat::make('Chat menunggu', (string) Conversation::where('status', ConversationStatus::WaitingAdmin)->count()),
        ];
    }
}
