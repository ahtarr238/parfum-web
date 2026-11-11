<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Models\ProductVariant;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('adjustStock')
                ->label('Ubah stok')
                ->icon('heroicon-o-arrows-up-down')
                ->form([
                    Forms\Components\Select::make('variant_id')
                        ->label('Ukuran')
                        ->options(fn () => $this->record->variants()->get()
                            ->mapWithKeys(fn (ProductVariant $v) => [$v->id => "{$v->label()} (stok sekarang {$v->stock})"]))
                        ->required(),
                    Forms\Components\TextInput::make('change')
                        ->label('Penyesuaian')
                        ->helperText('Angka positif untuk menambah (barang datang), negatif untuk mengurangi (rusak/hilang).')
                        ->numeric()
                        ->integer()
                        ->required()
                        ->notIn([0]),
                ])
                ->action(function (array $data) {
                    $result = DB::transaction(function () use ($data) {
                        $variant = $this->record->variants()->whereKey($data['variant_id'])->lockForUpdate()->firstOrFail();
                        $new = $variant->stock + (int) $data['change'];

                        if ($new < 0) {
                            return "Stok tidak bisa kurang dari 0 (sekarang {$variant->stock}).";
                        }

                        $variant->update(['stock' => $new]);

                        return null;
                    });

                    $result
                        ? Notification::make()->danger()->title($result)->send()
                        : Notification::make()->success()->title('Stok diperbarui')->send();

                    $this->refreshFormData(['variants']);
                }),
            Actions\Action::make('view')
                ->label('Lihat di toko')
                ->url(fn () => route('products.show', $this->record))
                ->openUrlInNewTab()
                ->color('gray'),
            Actions\DeleteAction::make(),
        ];
    }
}
