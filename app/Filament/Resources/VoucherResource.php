<?php

namespace App\Filament\Resources;

use App\Enums\VoucherType;
use App\Filament\Resources\VoucherResource\Pages;
use App\Models\Voucher;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class VoucherResource extends Resource
{
    protected static ?string $model = Voucher::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationGroup = 'Penjualan';

    protected static ?string $modelLabel = 'voucher';

    protected static ?string $pluralModelLabel = 'voucher';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('code')
                ->label('Kode')
                ->required()
                ->maxLength(50)
                ->alphaDash()
                ->unique(ignoreRecord: true)
                ->dehydrateStateUsing(fn (?string $state) => strtoupper(trim((string) $state))),
            Forms\Components\Select::make('type')
                ->label('Tipe')
                ->options(collect(VoucherType::cases())->mapWithKeys(fn (VoucherType $t) => [$t->value => $t->label()]))
                ->required()
                ->live(),
            Forms\Components\TextInput::make('value')
                ->label('Nilai')
                ->numeric()
                ->integer()
                ->required()
                ->minValue(1)
                ->maxValue(fn (Get $get) => $get('type') === VoucherType::Percent->value ? 100 : null)
                ->prefix(fn (Get $get) => $get('type') === VoucherType::Percent->value ? null : 'Rp')
                ->suffix(fn (Get $get) => $get('type') === VoucherType::Percent->value ? '%' : null),
            Forms\Components\TextInput::make('min_purchase')->label('Minimal belanja')->prefix('Rp')->numeric()->integer()->minValue(0)->default(0)->required(),
            Forms\Components\TextInput::make('quota')->label('Kuota')->numeric()->integer()->minValue(1)->required(),
            Forms\Components\DateTimePicker::make('starts_at')->label('Mulai')->required()->seconds(false),
            Forms\Components\DateTimePicker::make('ends_at')->label('Berakhir')->required()->seconds(false)->after('starts_at'),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->label('Kode')->searchable()->copyable()->weight('medium'),
                Tables\Columns\TextColumn::make('value')->label('Potongan')
                    ->formatStateUsing(fn (Voucher $record) => $record->type === VoucherType::Percent ? "{$record->value}%" : rupiah($record->value)),
                Tables\Columns\TextColumn::make('min_purchase')->label('Min. belanja')->money('IDR', locale: 'id'),
                Tables\Columns\TextColumn::make('used')->label('Terpakai')
                    ->formatStateUsing(fn (Voucher $record) => "{$record->used} / {$record->quota}"),
                Tables\Columns\TextColumn::make('ends_at')->label('Berlaku')
                    ->formatStateUsing(fn (Voucher $record) => $record->starts_at->format('j M').' – '.$record->ends_at->format('j M Y'))
                    ->color(fn (Voucher $record) => $record->ends_at->isPast() ? 'danger' : null),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageVouchers::route('/'),
        ];
    }
}
