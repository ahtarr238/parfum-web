<?php

namespace App\Filament\Widgets;

use App\Models\OrderItem;
use App\Models\Product;
use App\Support\StoreMetrics;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class TopProductsTable extends TableWidget
{
    protected static ?string $heading = 'Produk terlaris';

    protected static ?int $sort = 3;

    public function table(Table $table): Table
    {
        $sold = OrderItem::query()
            ->selectRaw('coalesce(sum(order_items.qty), 0)')
            ->join('product_variants', 'product_variants.id', '=', 'order_items.product_variant_id')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereColumn('product_variants.product_id', 'products.id')
            ->whereIn('orders.status', StoreMetrics::paidStatuses());

        return $table
            ->query(Product::query()->with('brand:id,name')->select('products.*')->selectSub($sold, 'sold_qty'))
            ->modifyQueryUsing(fn ($query) => $query->having('sold_qty', '>', 0)->orderByDesc('sold_qty'))
            ->paginated([5])
            ->defaultPaginationPageOption(5)
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Produk')->description(fn (Product $record) => $record->brand->name),
                Tables\Columns\TextColumn::make('sold_qty')->label('Terjual')->numeric(),
            ])
            ->emptyStateHeading('Belum ada penjualan');
    }
}
