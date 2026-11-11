<?php

namespace App\Filament\Resources;

use App\Enums\OrderStatus;
use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationGroup = 'Penjualan';

    protected static ?string $modelLabel = 'pesanan';

    protected static ?string $pluralModelLabel = 'pesanan';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'code';

    public static function getNavigationBadge(): ?string
    {
        $count = Order::whereIn('status', [OrderStatus::Paid, OrderStatus::Processing])->count();

        return $count ? (string) $count : null;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('user'))
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('No. pesanan')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('user.name')->label('Customer')->searchable(),
                Tables\Columns\TextColumn::make('status')->badge()
                    ->formatStateUsing(fn (OrderStatus $state) => $state->label())
                    ->color(fn (OrderStatus $state) => $state->color()),
                Tables\Columns\TextColumn::make('items_count')->label('Item')->counts('items'),
                Tables\Columns\TextColumn::make('total')->money('IDR', locale: 'id')->sortable(),
                Tables\Columns\TextColumn::make('courier')->label('Kurir')
                    ->formatStateUsing(fn (Order $record) => strtoupper((string) $record->courier).' '.$record->courier_service),
                Tables\Columns\TextColumn::make('tracking_number')->label('Resi')->placeholder('–')->copyable(),
                Tables\Columns\TextColumn::make('created_at')->label('Dibuat')->dateTime('j M Y, H:i')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(OrderStatus::options()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Pesanan')->schema([
                Infolists\Components\TextEntry::make('code')->label('No. pesanan')->copyable(),
                Infolists\Components\TextEntry::make('status')->badge()
                    ->formatStateUsing(fn (OrderStatus $state) => $state->label())
                    ->color(fn (OrderStatus $state) => $state->color()),
                Infolists\Components\TextEntry::make('created_at')->label('Dibuat')->dateTime('j M Y, H:i'),
                Infolists\Components\TextEntry::make('expires_at')->label('Batas bayar')->dateTime('j M Y, H:i')
                    ->visible(fn (Order $record) => $record->status === OrderStatus::PendingPayment),
                Infolists\Components\TextEntry::make('user.name')->label('Customer'),
                Infolists\Components\TextEntry::make('user.email')->label('Email')->copyable(),
            ])->columns(3),

            Infolists\Components\Section::make('Pengiriman')->schema([
                Infolists\Components\TextEntry::make('courier')->label('Kurir')
                    ->formatStateUsing(fn (Order $record) => strtoupper((string) $record->courier).' '.$record->courier_service),
                Infolists\Components\TextEntry::make('tracking_number')->label('No. resi')->placeholder('Belum diinput')->copyable(),
                Infolists\Components\TextEntry::make('address_snapshot')->label('Alamat')
                    ->formatStateUsing(function (Order $record) {
                        $a = $record->address_snapshot;

                        return ($a['recipient'] ?? '').' ('.($a['phone'] ?? '').")\n"
                            .($a['detail'] ?? '').', '.region_name($a['district_name'] ?? '').', '
                            .region_name($a['city_name'] ?? '').', '.region_name($a['province_name'] ?? '').' '.($a['postal_code'] ?? '');
                    })
                    ->columnSpanFull(),
            ])->columns(2),

            Infolists\Components\Section::make('Barang')->schema([
                Infolists\Components\RepeatableEntry::make('items')->label('')->schema([
                    Infolists\Components\TextEntry::make('product_name')->label('Produk'),
                    Infolists\Components\TextEntry::make('size_ml')->label('Ukuran')->suffix(' ml'),
                    Infolists\Components\TextEntry::make('qty')->label('Jumlah'),
                    Infolists\Components\TextEntry::make('price')->label('Harga')->money('IDR', locale: 'id'),
                ])->columns(4),
                Infolists\Components\Grid::make(4)->schema([
                    Infolists\Components\TextEntry::make('subtotal')->money('IDR', locale: 'id'),
                    Infolists\Components\TextEntry::make('shipping_cost')->label('Ongkir')->money('IDR', locale: 'id'),
                    Infolists\Components\TextEntry::make('discount')->label('Diskon')->money('IDR', locale: 'id'),
                    Infolists\Components\TextEntry::make('total')->money('IDR', locale: 'id')->weight('bold'),
                ]),
            ]),
        ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'view' => Pages\ViewOrder::route('/{record}'),
        ];
    }
}
