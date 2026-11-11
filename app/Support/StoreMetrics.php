<?php

namespace App\Support;

use App\Enums\OrderStatus;
use App\Models\Order;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

/**
 * Angka dashboard admin. Omzet = total order yang sudah dibayar (Dibayar s/d Selesai).
 */
class StoreMetrics
{
    /**
     * @return list<string>
     */
    public static function paidStatuses(): array
    {
        return array_map(fn (OrderStatus $s) => $s->value, [
            OrderStatus::Paid, OrderStatus::Processing, OrderStatus::Shipped, OrderStatus::Completed,
        ]);
    }

    public function revenueBetween(CarbonInterface $from, CarbonInterface $to): int
    {
        return (int) $this->paid()->whereBetween('created_at', [$from, $to])->sum('total');
    }

    public function paidOrderCountBetween(CarbonInterface $from, CarbonInterface $to): int
    {
        return $this->paid()->whereBetween('created_at', [$from, $to])->count();
    }

    /**
     * Omzet per hari, terlama di depan. Kunci: tanggal Y-m-d.
     *
     * @return array<string, int>
     */
    public function dailyRevenue(int $days): array
    {
        $from = now()->subDays($days - 1)->startOfDay();

        $rows = $this->paid()
            ->where('created_at', '>=', $from)
            ->selectRaw('DATE(created_at) as day, SUM(total) as revenue')
            ->groupBy('day')
            ->pluck('revenue', 'day');

        $series = [];
        for ($i = 0; $i < $days; $i++) {
            $day = $from->copy()->addDays($i)->toDateString();
            $series[$day] = (int) ($rows[$day] ?? 0);
        }

        return $series;
    }

    private function paid(): Builder
    {
        return Order::query()->whereIn('status', self::paidStatuses());
    }
}
