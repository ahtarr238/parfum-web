<?php

namespace App\Filament\Pages;

use App\Enums\ConversationStatus;
use App\Enums\MessageSender;
use App\Models\Conversation;
use App\Services\ChatService;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;

class ChatInbox extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationGroup = 'Layanan';

    protected static ?string $navigationLabel = 'Inbox chat';

    protected static ?string $title = 'Inbox chat';

    protected static ?string $slug = 'inbox-chat';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.chat-inbox';

    public const TABS = [
        'waiting_admin' => 'Menunggu',
        'open' => 'Dibalas',
        'bot' => 'Chatbot',
        'closed' => 'Selesai',
    ];

    #[Url]
    public string $tab = 'waiting_admin';

    #[Url(as: 'c')]
    public ?int $selectedId = null;

    public string $reply = '';

    public static function getNavigationBadge(): ?string
    {
        $count = Conversation::where('status', ConversationStatus::WaitingAdmin)->count();

        return $count ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    /** @return Collection<int, Conversation> */
    #[Computed]
    public function conversations(): Collection
    {
        return Conversation::query()
            ->with(['user:id,name,email', 'latestMessage'])
            ->withCount(['messages as unread_count' => fn ($q) => $q->where('sender_type', MessageSender::Customer)->whereNull('read_at')])
            ->where('status', ConversationStatus::tryFrom($this->tab) ?? ConversationStatus::WaitingAdmin)
            ->orderByDesc('last_message_at')
            ->limit(100)
            ->get();
    }

    #[Computed]
    public function selected(): ?Conversation
    {
        return $this->selectedId
            ? Conversation::with(['user:id,name,email,phone', 'messages'])->find($this->selectedId)
            : null;
    }

    /** @return array<string, int> */
    #[Computed]
    public function counts(): array
    {
        return Conversation::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status')->all();
    }

    public function setTab(string $tab): void
    {
        $this->tab = array_key_exists($tab, self::TABS) ? $tab : 'waiting_admin';
        $this->selectedId = null;
    }

    public function select(int $id): void
    {
        $this->selectedId = Conversation::findOrFail($id)->id;
        $this->reply = '';
        $this->resetErrorBag();
    }

    public function sendReply(ChatService $chat): void
    {
        $this->validate(['reply' => ['required', 'string', 'max:'.ChatService::MAX_LENGTH]], attributes: ['reply' => 'balasan']);

        if (! $this->selected) {
            return;
        }

        try {
            $chat->adminReplies($this->selected, auth()->user(), $this->reply);
        } catch (InvalidArgumentException $e) {
            $this->addError('reply', $e->getMessage());

            return;
        }

        $this->reply = '';
        // Percakapan pindah ke "Dibalas"; ikut pindah tab supaya admin tidak kehilangan konteks.
        $this->tab = ConversationStatus::Open->value;
        unset($this->selected, $this->conversations, $this->counts);
    }

    public function closeConversation(ChatService $chat): void
    {
        if ($this->selected) {
            $chat->close($this->selected);
        }

        unset($this->selected, $this->conversations, $this->counts);
    }

    public function poll(): void
    {
        unset($this->selected, $this->conversations, $this->counts);
    }
}
