<?php

namespace App\Filament\Resources;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Filament\Resources\CustomerResource\Pages;
use App\Models\User;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CustomerResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Penjualan';

    protected static ?string $modelLabel = 'customer';

    protected static ?string $pluralModelLabel = 'customer';

    protected static ?string $slug = 'customers';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('role', UserRole::Customer);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->label('Nama')->searchable(),
                Tables\Columns\TextColumn::make('email')->searchable()->copyable(),
                Tables\Columns\TextColumn::make('phone')->label('No. HP')->placeholder('–'),
                Tables\Columns\TextColumn::make('orders_count')->label('Pesanan')->counts('orders')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->label('Daftar')->date('j M Y')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([Tables\Actions\ViewAction::make()]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Profil')->schema([
                Infolists\Components\TextEntry::make('name')->label('Nama'),
                Infolists\Components\TextEntry::make('email')->copyable(),
                Infolists\Components\TextEntry::make('phone')->label('No. HP')->placeholder('–'),
                Infolists\Components\TextEntry::make('created_at')->label('Daftar')->date('j M Y'),
                Infolists\Components\TextEntry::make('spent')->label('Total belanja')
                    ->getStateUsing(fn (User $record) => rupiah((int) $record->orders()->whereIn('status', [
                        OrderStatus::Paid, OrderStatus::Processing, OrderStatus::Shipped, OrderStatus::Completed,
                    ])->sum('total'))),
            ])->columns(3),
            Infolists\Components\Section::make('Pesanan terakhir')->schema([
                Infolists\Components\RepeatableEntry::make('orders')->label('')
                    ->getStateUsing(fn (User $record) => $record->orders()->latest()->limit(10)->get())
                    ->schema([
                        Infolists\Components\TextEntry::make('code')->label('No. pesanan')
                            ->url(fn ($record) => OrderResource::getUrl('view', ['record' => $record])),
                        Infolists\Components\TextEntry::make('status')
                            ->formatStateUsing(fn (OrderStatus $state) => $state->label())->badge()
                            ->color(fn (OrderStatus $state) => $state->color()),
                        Infolists\Components\TextEntry::make('total')->money('IDR', locale: 'id'),
                        Infolists\Components\TextEntry::make('created_at')->label('Tanggal')->date('j M Y'),
                    ])->columns(4),
            ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'view' => Pages\ViewCustomer::route('/{record}'),
        ];
    }
}
