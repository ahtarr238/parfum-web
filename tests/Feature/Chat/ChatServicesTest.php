<?php

use App\Enums\ConversationStatus;
use App\Enums\MessageSender;
use App\Models\Conversation;
use App\Models\Faq;
use App\Models\User;
use App\Services\ChatbotService;
use App\Services\ChatService;

beforeEach(function () {
    $this->ongkir = Faq::factory()->create([
        'question' => 'Ongkir', 'keywords' => 'ongkir,ongkos kirim,kurir', 'answer' => 'Ongkir dihitung otomatis saat checkout.',
    ]);
    $this->ori = Faq::factory()->create([
        'question' => 'Produk ori?', 'keywords' => 'ori,original,asli', 'answer' => 'Semua produk original.',
    ]);
    Faq::factory()->create(['question' => 'Nonaktif', 'keywords' => 'rahasia', 'answer' => 'x', 'is_active' => false]);
    $this->bot = app(ChatbotService::class);
    $this->chat = app(ChatService::class);
});

it('mencocokkan keyword tanpa peduli huruf besar dan tanda baca', function () {
    expect($this->bot->match('Berapa ONGKOS KIRIM ke Bandung?')?->id)->toBe($this->ongkir->id)
        ->and($this->bot->match('ini parfum asli kan??')?->id)->toBe($this->ori->id);
});

it('mencocokkan teks pertanyaan FAQ dari tombol cepat', function () {
    expect($this->bot->match('Produk ori?')?->id)->toBe($this->ori->id);
});

it('tidak mencocokkan potongan kata dan FAQ nonaktif', function () {
    expect($this->bot->match('saya mau origami'))->toBeNull()
        ->and($this->bot->match('ada yang rahasia?'))->toBeNull()
        ->and($this->bot->match('halo'))->toBeNull();
});

it('memilih FAQ dengan keyword cocok terbanyak', function () {
    expect($this->bot->match('kurir apa dan berapa ongkir, original kan?')?->id)->toBe($this->ongkir->id);
});

it('chatbot menjawab pertanyaan yang dikenal', function () {
    $user = User::factory()->create();
    $conversation = $this->chat->conversationFor($user, 'tok');

    $this->chat->customerSends($conversation, 'ongkir berapa?', $user);

    $messages = $conversation->fresh()->messages;
    expect($messages)->toHaveCount(2)
        ->and($messages[1]->sender_type)->toBe(MessageSender::Bot)
        ->and($messages[1]->body)->toBe('Ongkir dihitung otomatis saat checkout.')
        ->and($conversation->fresh()->status)->toBe(ConversationStatus::Bot);
});

it('pertanyaan tak dikenal dari customer diteruskan ke admin', function () {
    $user = User::factory()->create();
    $conversation = $this->chat->conversationFor($user, 'tok');

    $this->chat->customerSends($conversation, 'bisa COD ke Papua?', $user);

    expect($conversation->fresh()->status)->toBe(ConversationStatus::WaitingAdmin)
        ->and($conversation->fresh()->messages->last()->sender_type)->toBe(MessageSender::Bot);
});

it('guest dengan pertanyaan tak dikenal diminta masuk dulu', function () {
    $conversation = $this->chat->conversationFor(null, 'tamu-1');

    $this->chat->customerSends($conversation, 'bisa COD?', null);

    expect($conversation->fresh()->status)->toBe(ConversationStatus::Bot)
        ->and($conversation->fresh()->messages->last()->body)->toContain('Masuk');
});

it('saat menunggu admin, pesan customer tidak dijawab bot', function () {
    $user = User::factory()->create();
    $conversation = $this->chat->conversationFor($user, 'tok');
    $this->chat->requestAdmin($conversation, $user);

    $this->chat->customerSends($conversation->fresh(), 'ongkir?', $user);

    expect($conversation->fresh()->messages->last()->sender_type)->toBe(MessageSender::Customer);
});

it('balasan admin mengubah status menjadi dibalas', function () {
    $user = User::factory()->create();
    $admin = User::factory()->admin()->create();
    $conversation = $this->chat->conversationFor($user, 'tok');
    $this->chat->requestAdmin($conversation, $user);

    $this->chat->adminReplies($conversation->fresh(), $admin, 'Halo, bisa kami bantu?');

    $last = $conversation->fresh()->messages->last();
    expect($conversation->fresh()->status)->toBe(ConversationStatus::Open)
        ->and($last->sender_type)->toBe(MessageSender::Admin)
        ->and($last->sender_id)->toBe($admin->id);
});

it('percakapan guest ikut pindah ke akun setelah login', function () {
    $guestConversation = $this->chat->conversationFor(null, 'tamu-2');
    $this->chat->customerSends($guestConversation, 'halo', null);
    $user = User::factory()->create();

    $conversation = $this->chat->conversationFor($user, 'tamu-2');

    expect($conversation->id)->toBe($guestConversation->id)
        ->and($conversation->user_id)->toBe($user->id);
});

it('percakapan yang ditutup memulai percakapan baru', function () {
    $user = User::factory()->create();
    $conversation = $this->chat->conversationFor($user, 'tok');
    $this->chat->close($conversation);

    expect($this->chat->conversationFor($user, 'tok')->id)->not->toBe($conversation->id);
});

it('guest tidak bisa melihat percakapan milik user lain lewat token', function () {
    $owner = User::factory()->create();
    $conversation = $this->chat->conversationFor($owner, 'shared');

    expect($this->chat->conversationFor(null, 'shared')->id)->not->toBe($conversation->id);
});

it('pesan kosong atau terlalu panjang ditolak', function () {
    $conversation = $this->chat->conversationFor(null, 'tamu-3');

    expect(fn () => $this->chat->customerSends($conversation, '   ', null))->toThrow(InvalidArgumentException::class);
    expect(fn () => $this->chat->customerSends($conversation, str_repeat('a', 1001), null))->toThrow(InvalidArgumentException::class);
    expect(Conversation::find($conversation->id)?->messages()->count() ?? 0)->toBe(0);
});
