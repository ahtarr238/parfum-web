<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BrandResource\Pages;
use App\Models\Brand;
use App\Support\ImageOptimizer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class BrandResource extends Resource
{
    protected static ?string $model = Brand::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationGroup = 'Katalog';

    protected static ?string $modelLabel = 'brand';

    protected static ?string $pluralModelLabel = 'brand';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema(static::formSchema());
    }

    /**
     * Dipakai juga untuk "buat brand baru" langsung dari form produk.
     *
     * @return array<int, Forms\Components\Component>
     */
    public static function formSchema(): array
    {
        return [
            Forms\Components\TextInput::make('name')
                ->label('Nama')
                ->required()
                ->maxLength(255)
                ->live(onBlur: true)
                ->afterStateUpdated(fn (Set $set, ?string $state, string $operation) => $operation === 'create' ? $set('slug', Str::slug((string) $state)) : null),
            Forms\Components\TextInput::make('slug')
                ->required()
                ->maxLength(255)
                ->alphaDash()
                ->unique(table: 'brands', ignoreRecord: true),
            Forms\Components\FileUpload::make('logo')
                ->image()
                ->disk('public')
                ->directory('brands')
                ->maxSize(2048)
                ->saveUploadedFileUsing(fn (TemporaryUploadedFile $file) => app(ImageOptimizer::class)->storeAsWebp($file, 'brands')),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('logo')->disk('public')->square(),
                Tables\Columns\TextColumn::make('name')->label('Nama')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('products_count')->label('Produk')->counts('products')->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (Tables\Actions\DeleteAction $action, Brand $record) {
                        if ($record->products()->exists()) {
                            Notification::make()->danger()
                                ->title('Brand masih dipakai produk')->send();
                            $action->cancel();
                        }
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageBrands::route('/'),
        ];
    }
}
