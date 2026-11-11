<?php

namespace App\Filament\Resources;

use App\Enums\ScentFamily;
use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\ImageOptimizer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ProductResource extends Resource
{
    public const LOW_STOCK = 5;

    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $navigationGroup = 'Katalog';

    protected static ?string $modelLabel = 'produk';

    protected static ?string $pluralModelLabel = 'produk';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Group::make([
                Forms\Components\Section::make('Informasi')->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('Nama parfum')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Set $set, ?string $state, string $operation) => $operation === 'create' ? $set('slug', Str::slug((string) $state)) : null),
                    Forms\Components\TextInput::make('slug')
                        ->required()
                        ->maxLength(255)
                        ->alphaDash()
                        ->unique(ignoreRecord: true),
                    Forms\Components\Textarea::make('description')
                        ->label('Deskripsi')
                        ->required()
                        ->rows(5)
                        ->columnSpanFull(),
                ])->columns(2),

                Forms\Components\Section::make('Notes')
                    ->description('Pisahkan tiap note dengan koma, misalnya: Bergamot, Lemon')
                    ->schema([
                        Forms\Components\TextInput::make('top_notes')->label('Top notes')->maxLength(255),
                        Forms\Components\TextInput::make('middle_notes')->label('Middle notes')->maxLength(255),
                        Forms\Components\TextInput::make('base_notes')->label('Base notes')->maxLength(255),
                    ]),

                Forms\Components\Section::make('Varian ukuran')->schema([
                    Forms\Components\Repeater::make('variants')
                        ->label('')
                        ->relationship()
                        ->schema([
                            Forms\Components\TextInput::make('size_ml')->label('Ukuran (ml)')->numeric()->integer()->minValue(1)->maxValue(1000)->required(),
                            Forms\Components\TextInput::make('sku')->label('SKU')->required()->maxLength(50)
                                ->unique(table: 'product_variants', column: 'sku', ignoreRecord: true),
                            Forms\Components\TextInput::make('price')->label('Harga')->prefix('Rp')->numeric()->integer()->minValue(1000)->required(),
                            // Stok varian yang sudah ada hanya diubah lewat aksi "Ubah stok" (atomik),
                            // supaya menyimpan form tidak menimpa stok yang berubah karena pesanan.
                            Forms\Components\TextInput::make('stock')->label('Stok')->numeric()->integer()->minValue(0)->required()->default(0)
                                ->disabled(fn (?ProductVariant $record) => $record?->exists ?? false)
                                ->dehydrated(fn (?ProductVariant $record) => ! ($record?->exists ?? false))
                                ->helperText(fn (?ProductVariant $record) => $record?->exists ? 'Ubah lewat tombol Ubah stok' : null),
                            Forms\Components\TextInput::make('weight_gram')->label('Berat kirim (gram)')->numeric()->integer()->minValue(1)->required(),
                            Forms\Components\Toggle::make('is_decant')->label('Decant')->inline(false),
                        ])
                        ->columns(6)
                        ->minItems(1)
                        ->defaultItems(1)
                        ->addActionLabel('Tambah ukuran')
                        ->reorderable(false),
                ]),
            ])->columnSpan(['lg' => 2]),

            Forms\Components\Group::make([
                Forms\Components\Section::make('Status')->schema([
                    Forms\Components\Toggle::make('is_active')
                        ->label('Aktif dijual')
                        ->helperText('Produk nonaktif tetap tampil dengan label "Habis".')
                        ->default(true),
                ]),
                Forms\Components\Section::make('Klasifikasi')->schema([
                    Forms\Components\Select::make('category_id')
                        ->label('Kategori')
                        ->relationship('category', 'name')
                        ->required()
                        ->preload(),
                    Forms\Components\Select::make('brand_id')
                        ->label('Brand')
                        ->relationship('brand', 'name')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->createOptionForm(BrandResource::formSchema()),
                    Forms\Components\Select::make('scent_family')
                        ->label('Keluarga aroma')
                        ->options(ScentFamily::options())
                        ->required(),
                ]),
                Forms\Components\Section::make('Foto')->schema([
                    Forms\Components\FileUpload::make('images')
                        ->label('')
                        ->helperText('Otomatis dikompres ke WebP. Foto pertama jadi sampul.')
                        ->multiple()
                        ->reorderable()
                        ->image()
                        ->maxFiles(8)
                        ->maxSize(5120)
                        ->disk('public')
                        ->directory('products')
                        ->saveUploadedFileUsing(fn (TemporaryUploadedFile $file) => app(ImageOptimizer::class)->storeAsWebp($file, 'products'))
                        ->default([]),
                ]),
            ])->columnSpan(['lg' => 1]),
        ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with(['brand', 'category'])
                ->withMin('variants as min_price', 'price')
                ->withMin('variants as min_stock', 'stock')
                ->withSum('variants as total_stock', 'stock'))
            ->columns([
                Tables\Columns\ImageColumn::make('images')->label('')->disk('public')->limit(1)->square(),
                Tables\Columns\TextColumn::make('name')->label('Nama')->searchable()->sortable()
                    ->description(fn (Product $record) => $record->brand->name),
                Tables\Columns\TextColumn::make('category.name')->label('Kategori'),
                Tables\Columns\TextColumn::make('scent_family')->label('Aroma')
                    ->formatStateUsing(fn (ScentFamily $state) => $state->label())->badge()->color('gray'),
                Tables\Columns\TextColumn::make('min_price')->label('Mulai')->money('IDR', locale: 'id')->sortable(),
                Tables\Columns\TextColumn::make('total_stock')->label('Stok')->numeric()->sortable()
                    ->color(fn (Product $record) => (int) $record->min_stock <= self::LOW_STOCK ? 'danger' : null)
                    ->description(fn (Product $record) => (int) $record->min_stock <= self::LOW_STOCK ? 'Ada ukuran menipis' : null),
                Tables\Columns\ToggleColumn::make('is_active')->label('Aktif'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('category')->label('Kategori')->relationship('category', 'name'),
                Tables\Filters\SelectFilter::make('brand')->label('Brand')->relationship('brand', 'name'),
                Tables\Filters\TernaryFilter::make('is_active')->label('Aktif'),
                Tables\Filters\Filter::make('low_stock')->label('Stok menipis')
                    ->query(fn (Builder $query) => $query->whereHas('variants', fn (Builder $v) => $v->where('stock', '<=', self::LOW_STOCK))),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
