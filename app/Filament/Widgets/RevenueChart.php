<?php

namespace App\Filament\Widgets;

use App\Support\StoreMetrics;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class RevenueChart extends ChartWidget
{
    protected static ?string $heading = 'Omzet 30 hari terakhir';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $series = app(StoreMetrics::class)->dailyRevenue(30);

        return [
            'datasets' => [[
                'label' => 'Omzet',
                'data' => array_values($series),
                'borderColor' => '#8a4b1f',
                'backgroundColor' => 'rgba(138, 75, 31, 0.08)',
                'fill' => true,
                'tension' => 0.35,
                'pointRadius' => 0,
            ]],
            'labels' => array_map(fn (string $d) => Carbon::parse($d)->translatedFormat('j M'), array_keys($series)),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['display' => false]],
            'scales' => ['y' => ['beginAtZero' => true]],
        ];
    }
}
