<?php

namespace App\Livewire;

use App\Enums\ConversationStatus;
use App\Models\Conversation;
use App\Services\ChatbotService;
use App\Services\ChatService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Widget chat di semua halaman. Pesan baru muncul lewat wire:poll (4 detik) saat panel terbuka.
 */
class ChatWidget extends Component
{
    public const MAX_PER_MINUTE = 15;

    public bool $open = false;

    public string $body = '';

    public ?int $conversationId = null;

    public function mount(): void
    {
        $this->conversationId = $this->findConversation()?->id;
    }

    public function send(ChatService $chat): void
    {
        $this->validate(['body' => ['required', 'string', 'max:'.ChatService::MAX_LENGTH]], attributes: ['body' => 'pesan']);

        if (! $this->hitRateLimit()) {
            return;
        }

        $this->post($chat, $this->body);
        $this->body = '';
    }

    public function ask(string $question, ChatService $chat): void
    {
        if (! $this->hitRateLimit()) {
            return;
        }

        $this->post($chat, Str::limit($question, ChatService::MAX_LENGTH, ''));
    }

    public function talkToAdmin(ChatService $chat): void
    {
        if (! auth()->check()) {
            session()->put('url.intended', url()->previous());
            $this->redirectRoute('login', navigate: true);

            return;
        }

        $conversation = $chat->conversationFor(auth()->user(), $this->token());
        $chat->requestAdmin($conversation, auth()->user());
        $this->conversationId = $conversation->id;
        unset($this->conversation);
    }

    public function poll(): void
    {
        unset($this->conversation);
    }

    #[Computed]
    public function conversation(): ?Conversation
    {
        $conversation = $this->conversationId ? Conversation::find($this->conversationId) : null;

        // Pastikan percakapan masih milik pengunjung ini.
        if ($conversation && ! $this->owns($conversation)) {
            return null;
        }

        return $conversation?->load('messages');
    }

    public function render(ChatbotService $bot): View
    {
        return view('livewire.chat-widget', [
            'quickQuestions' => $this->open ? $bot->quickQuestions() : collect(),
            'waiting' => $this->conversation?->status === ConversationStatus::WaitingAdmin,
            'withAdmin' => in_array($this->conversation?->status, [ConversationStatus::WaitingAdmin, ConversationStatus::Open], true),
        ]);
    }

    private function post(ChatService $chat, string $text): void
    {
        $conversation = $chat->conversationFor(auth()->user(), $this->token());

        try {
            $chat->customerSends($conversation, $text, auth()->user());
        } catch (InvalidArgumentException $e) {
            $this->addError('body', $e->getMessage());

            return;
        }

        $this->conversationId = $conversation->id;
        unset($this->conversation);
    }

    private function hitRateLimit(): bool
    {
        $key = 'chat:'.(auth()->id() ?? request()->ip());

        if (RateLimiter::tooManyAttempts($key, self::MAX_PER_MINUTE)) {
            $this->addError('body', 'Terlalu banyak pesan. Tunggu sebentar lalu coba lagi.');

            return false;
        }

        RateLimiter::hit($key, 60);

        return true;
    }

    private function findConversation(): ?Conversation
    {
        $query = Conversation::where('status', '!=', ConversationStatus::Closed)->latest('id');

        return auth()->check()
            ? $query->where('user_id', auth()->id())->first()
            : $query->whereNull('user_id')->where('session_id', $this->token())->first();
    }

    private function owns(Conversation $conversation): bool
    {
        return auth()->check()
            ? $conversation->user_id === auth()->id()
            : $conversation->user_id === null && $conversation->session_id === $this->token();
    }

    private function token(): string
    {
        if (! session()->has('chat_token')) {
            session()->put('chat_token', Str::random(40));
        }

        return session('chat_token');
    }
}
