<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Enums\OrderStatus;
use App\Exceptions\OrderException;
use App\Filament\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

/**
 * Status Dibayar sengaja tidak punya aksi di sini: hanya webhook Midtrans yang boleh mengubahnya.
 */
class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('process')
                ->label('Tandai dikemas')
                ->icon('heroicon-o-archive-box')
                ->visible(fn (Order $record) => $record->status === OrderStatus::Paid)
                ->action(fn (Order $record) => $this->run(fn (OrderService $s) => $s->markProcessing($record), 'Pesanan ditandai dikemas')),

            Actions\Action::make('ship')
                ->label('Input resi')
                ->icon('heroicon-o-truck')
                ->color('primary')
                ->visible(fn (Order $record) => in_array($record->status, [OrderStatus::Paid, OrderStatus::Processing], true))
                ->form([
                    Forms\Components\TextInput::make('tracking_number')
                        ->label('Nomor resi')
                        ->required()
                        ->maxLength(100),
                ])
                ->modalDescription('Status berubah menjadi Dikirim dan customer menerima email berisi resi.')
                ->action(fn (Order $record, array $data) => $this->run(fn (OrderService $s) => $s->ship($record, $data['tracking_number']), 'Resi tersimpan, email dikirim ke customer')),

            Actions\Action::make('cancel')
                ->label('Batalkan')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalDescription('Stok dikembalikan dan kuota voucher dilepas. Jika customer sudah membayar, proses refund dilakukan manual.')
                ->visible(fn (Order $record) => in_array($record->status, [OrderStatus::PendingPayment, OrderStatus::Paid, OrderStatus::Processing], true))
                ->action(fn (Order $record) => $this->run(fn (OrderService $s) => $s->cancel($record), 'Pesanan dibatalkan')),
        ];
    }

    private function run(callable $action, string $success): void
    {
        try {
            $action(app(OrderService::class));
            Notification::make()->success()->title($success)->send();
        } catch (OrderException $e) {
            Notification::make()->danger()->title($e->getMessage())->send();
        }

        $this->record->refresh();
    }
}
