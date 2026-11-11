<?php

use App\Enums\ConversationStatus;
use App\Filament\Pages\ChatInbox;
use App\Filament\Resources\FaqResource\Pages\ManageFaqs;
use App\Livewire\ChatWidget;
use App\Models\Conversation;
use App\Models\Faq;
use App\Models\User;
use App\Services\ChatService;
use Livewire\Livewire;

beforeEach(function () {
    Faq::factory()->create(['question' => 'Ongkir', 'keywords' => 'ongkir', 'answer' => 'Ongkir dihitung otomatis.']);
    Faq::factory()->create(['question' => 'Cara order', 'keywords' => 'order', 'answer' => 'Pilih produk lalu checkout.']);
});

it('widget chat ada di semua halaman', function () {
    $this->get('/')->assertSeeLivewire(ChatWidget::class);
    $this->get('/katalog')->assertSeeLivewire(ChatWidget::class);
});

it('tombol cepat diambil dari tabel FAQ', function () {
    Livewire::test(ChatWidget::class)
        ->set('open', true)
        ->assertSee('Ongkir')
        ->assertSee('Cara order');
});

it('guest bertanya lewat tombol cepat dan dijawab chatbot', function () {
    Livewire::test(ChatWidget::class)
        ->set('open', true)
        ->call('ask', 'Ongkir')
        ->assertSee('Ongkir dihitung otomatis.');
});

it('customer menekan bicara dengan admin dan masuk inbox menunggu', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)->test(ChatWidget::class)
        ->set('open', true)
        ->call('talkToAdmin')
        ->assertSee('teruskan ke admin');

    expect(Conversation::where('user_id', $user->id)->first()->status)->toBe(ConversationStatus::WaitingAdmin);
});

it('guest yang menekan bicara dengan admin diarahkan ke login', function () {
    Livewire::test(ChatWidget::class)
        ->set('open', true)
        ->call('talkToAdmin')
        ->assertRedirect(route('login'));
});

it('pesan baru dari admin muncul saat polling', function () {
    $user = User::factory()->create();
    $admin = User::factory()->admin()->create();

    $widget = Livewire::actingAs($user)->test(ChatWidget::class)
        ->set('open', true)
        ->set('body', 'Halo, stok Hutan Pagi 100ml kapan datang?')
        ->call('send');

    $conversation = Conversation::where('user_id', $user->id)->first();
    app(ChatService::class)->adminReplies($conversation, $admin, 'Minggu depan kak.');

    $widget->call('poll')->assertSee('Minggu depan kak.');
});

it('chat dibatasi rate limit', function () {
    $user = User::factory()->create();
    $widget = Livewire::actingAs($user)->test(ChatWidget::class)->set('open', true);

    for ($i = 0; $i < ChatWidget::MAX_PER_MINUTE; $i++) {
        $widget->set('body', "pesan {$i}")->call('send')->assertHasNoErrors();
    }

    $widget->set('body', 'satu lagi')->call('send')->assertHasErrors('body');
});

it('admin melihat percakapan menunggu dan membalasnya', function () {
    $user = User::factory()->create(['name' => 'Siti Rahma']);
    $admin = User::factory()->admin()->create();
    $chat = app(ChatService::class);
    $conversation = $chat->conversationFor($user, 'tok');
    $chat->customerSends($conversation, 'bisa kirim hari ini?', $user);

    Livewire::actingAs($admin)->test(ChatInbox::class)
        ->assertSee('Siti Rahma')
        ->call('select', $conversation->id)
        ->assertSee('bisa kirim hari ini?')
        ->set('reply', 'Bisa, sebelum jam 3 sore.')
        ->call('sendReply')
        ->assertHasNoErrors();

    expect($conversation->fresh()->status)->toBe(ConversationStatus::Open)
        ->and($conversation->fresh()->messages->last()->body)->toBe('Bisa, sebelum jam 3 sore.');
});

it('admin menutup percakapan', function () {
    $user = User::factory()->create();
    $admin = User::factory()->admin()->create();
    $conversation = app(ChatService::class)->conversationFor($user, 'tok');
    app(ChatService::class)->requestAdmin($conversation, $user);

    Livewire::actingAs($admin)->test(ChatInbox::class)
        ->call('select', $conversation->id)
        ->call('closeConversation');

    expect($conversation->fresh()->status)->toBe(ConversationStatus::Closed);
});

it('customer tidak bisa membuka inbox admin', function () {
    $this->actingAs(User::factory()->create())->get(ChatInbox::getUrl())->assertForbidden();
});

it('admin menambah FAQ', function () {
    Livewire::actingAs(User::factory()->admin()->create())->test(ManageFaqs::class)
        ->callAction('create', data: [
            'question' => 'Bisa retur?',
            'keywords' => 'retur, tukar, kembalikan',
            'answer' => 'Retur bisa dalam 3 hari jika segel belum dibuka.',
            'is_active' => true,
        ])
        ->assertHasNoActionErrors();

    expect(Faq::where('question', 'Bisa retur?')->first()->keywords)->toBe('retur,tukar,kembalikan');
});
