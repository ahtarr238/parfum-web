<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FaqResource\Pages;
use App\Models\Faq;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class FaqResource extends Resource
{
    protected static ?string $model = Faq::class;

    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static ?string $navigationGroup = 'Layanan';

    protected static ?string $modelLabel = 'FAQ';

    protected static ?string $pluralModelLabel = 'FAQ chatbot';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('question')
                ->label('Pertanyaan')
                ->helperText('Juga dipakai sebagai tombol cepat di widget chat.')
                ->required()
                ->maxLength(255),
            Forms\Components\TextInput::make('keywords')
                ->label('Keyword')
                ->helperText('Pisahkan dengan koma. Chatbot mencocokkan kata atau frasa utuh, tanpa peduli huruf besar.')
                ->required()
                ->maxLength(500)
                ->dehydrateStateUsing(fn (?string $state) => collect(explode(',', (string) $state))
                    ->map(fn (string $k) => mb_strtolower(trim($k)))
                    ->filter()
                    ->unique()
                    ->implode(',')),
            Forms\Components\Textarea::make('answer')
                ->label('Jawaban')
                ->required()
                ->rows(4)
                ->maxLength(1000),
            Forms\Components\Toggle::make('is_active')->label('Aktif')->default(true),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('question')->label('Pertanyaan')->searchable()->wrap(),
                Tables\Columns\TextColumn::make('keywords')->label('Keyword')->limit(60)->color('gray'),
                Tables\Columns\ToggleColumn::make('is_active')->label('Aktif'),
            ])
            ->reorderable(false)
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageFaqs::route('/'),
        ];
    }
}
