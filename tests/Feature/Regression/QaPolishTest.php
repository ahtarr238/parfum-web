<?php

use App\Enums\ConversationStatus;
use App\Filament\Pages\ChatInbox;
use App\Models\User;
use App\Notifications\QueuedResetPassword;
use App\Services\ChatService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

it('format nama wilayah rapi dan mempertahankan singkatan', function () {
    expect(region_name('DKI JAKARTA'))->toBe('DKI Jakarta')
        ->and(region_name('DI YOGYAKARTA'))->toBe('DI Yogyakarta')
        ->and(region_name('KOTA BANDUNG'))->toBe('Kota Bandung')
        ->and(region_name('SURAKARTA (SOLO)'))->toBe('Surakarta (Solo)')
        ->and(region_name(null))->toBe('');
});

it('email reset password dikirim lewat queue', function () {
    Notification::fake();
    $user = User::factory()->create();

    $user->sendPasswordResetNotification('token');

    Notification::assertSentTo($user, QueuedResetPassword::class, fn ($n) => $n instanceof ShouldQueue);
});

it('setelah admin membalas, inbox pindah ke tab Dibalas dengan percakapan tetap terpilih', function () {
    $user = User::factory()->create();
    $conversation = app(ChatService::class)->conversationFor($user, 'tok');
    app(ChatService::class)->requestAdmin($conversation, $user);

    Livewire::actingAs(User::factory()->admin()->create())->test(ChatInbox::class)
        ->call('select', $conversation->id)
        ->set('reply', 'Halo')
        ->call('sendReply')
        ->assertSet('tab', ConversationStatus::Open->value)
        ->assertSet('selectedId', $conversation->id);
});
