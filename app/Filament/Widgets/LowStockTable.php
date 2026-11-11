<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ProductResource;
use App\Models\ProductVariant;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LowStockTable extends TableWidget
{
    protected static ?string $heading = 'Stok menipis';

    protected static ?int $sort = 4;

    public function table(Table $table): Table
    {
        return $table
            ->query(ProductVariant::query()->with('product')->where('stock', '<=', ProductResource::LOW_STOCK)->orderBy('stock'))
            ->paginated([5])
            ->defaultPaginationPageOption(5)
            ->columns([
                Tables\Columns\TextColumn::make('product.name')->label('Produk'),
                Tables\Columns\TextColumn::make('size_ml')->label('Ukuran')
                    ->formatStateUsing(fn (ProductVariant $record) => $record->label()),
                Tables\Columns\TextColumn::make('stock')->label('Stok')->badge()
                    ->color(fn (int $state) => $state === 0 ? 'danger' : 'warning'),
            ])
            ->recordUrl(fn (ProductVariant $record) => ProductResource::getUrl('edit', ['record' => $record->product]))
            ->emptyStateHeading('Semua stok aman');
    }
}
