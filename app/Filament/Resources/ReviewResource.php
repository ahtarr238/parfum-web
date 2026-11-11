<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReviewResource\Pages;
use App\Models\Review;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static ?string $navigationGroup = 'Layanan';

    protected static ?string $modelLabel = 'ulasan';

    protected static ?string $pluralModelLabel = 'ulasan';

    protected static ?int $navigationSort = 3;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['user:id,name', 'product:id,name']))
            ->columns([
                Tables\Columns\TextColumn::make('product.name')->label('Produk')->searchable(),
                Tables\Columns\TextColumn::make('user.name')->label('Customer'),
                Tables\Columns\TextColumn::make('rating')->label('Rating')->formatStateUsing(fn (int $state) => str_repeat('★', $state).str_repeat('☆', 5 - $state))->color('warning'),
                Tables\Columns\TextColumn::make('comment')->label('Ulasan')->wrap()->limit(120)->placeholder('–'),
                Tables\Columns\IconColumn::make('is_visible')->label('Tampil')->boolean(),
                Tables\Columns\TextColumn::make('created_at')->label('Tanggal')->date('j M Y')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_visible')->label('Tampil'),
                Tables\Filters\SelectFilter::make('rating')->options([1 => '1', 2 => '2', 3 => '3', 4 => '4', 5 => '5']),
            ])
            ->actions([
                Tables\Actions\Action::make('toggleVisibility')
                    ->label(fn (Review $record) => $record->is_visible ? 'Sembunyikan' : 'Tampilkan')
                    ->icon(fn (Review $record) => $record->is_visible ? 'heroicon-o-eye-slash' : 'heroicon-o-eye')
                    ->color('gray')
                    ->action(fn (Review $record) => $record->update(['is_visible' => ! $record->is_visible])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageReviews::route('/'),
        ];
    }
}
