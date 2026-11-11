<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('view')
                ->label('Lihat di toko')
                ->url(fn () => route('products.show', $this->record))
                ->openUrlInNewTab()
                ->color('gray'),
            Actions\DeleteAction::make(),
        ];
    }
}
