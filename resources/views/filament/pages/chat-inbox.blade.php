<x-filament-panels::page>
    <div wire:poll.4s="poll" class="grid gap-4 lg:grid-cols-[320px_1fr]">
        <div class="space-y-3">
            <x-filament::tabs>
                @foreach (\App\Filament\Pages\ChatInbox::TABS as $value => $label)
                    <x-filament::tabs.item :active="$tab === $value" wire:click="setTab('{{ $value }}')"
                        :badge="($this->counts[$value] ?? 0) ?: null">
                        {{ $label }}
                    </x-filament::tabs.item>
                @endforeach
            </x-filament::tabs>

            <x-filament::section :compact="true">
                <div class="-m-2 divide-y divide-gray-100 dark:divide-white/5">
                    @forelse ($this->conversations as $conversation)
                        <button type="button" wire:key="conv-{{ $conversation->id }}" wire:click="select({{ $conversation->id }})"
                            @class([
                                'flex w-full flex-col items-start gap-0.5 rounded-lg px-3 py-2.5 text-left transition',
                                'bg-primary-50 dark:bg-white/5' => $selectedId === $conversation->id,
                                'hover:bg-gray-50 dark:hover:bg-white/5' => $selectedId !== $conversation->id,
                            ])>
                            <span class="flex w-full items-center justify-between gap-2">
                                <span class="truncate text-sm font-medium text-gray-950 dark:text-white">{{ $conversation->user?->name ?? 'Tamu' }}</span>
                                <span class="shrink-0 text-xs text-gray-500">{{ $conversation->last_message_at?->diffForHumans(short: true) }}</span>
                            </span>
                            <span class="flex w-full items-center justify-between gap-2">
                                <span class="truncate text-xs text-gray-500">{{ \Illuminate\Support\Str::limit($conversation->latestMessage?->body, 60) }}</span>
                                @if ($conversation->unread_count)
                                    <x-filament::badge size="sm" color="warning">{{ $conversation->unread_count }}</x-filament::badge>
                                @endif
                            </span>
                        </button>
                    @empty
                        <p class="px-3 py-6 text-sm text-gray-500">Tidak ada percakapan.</p>
                    @endforelse
                </div>
            </x-filament::section>
        </div>

        <x-filament::section>
            @if ($this->selected)
                <x-slot name="heading">{{ $this->selected->user?->name ?? 'Tamu' }}</x-slot>
                <x-slot name="description">
                    {{ $this->selected->user?->email }}
                    @if ($this->selected->user?->phone) · {{ $this->selected->user->phone }} @endif
                    · {{ $this->selected->status->label() }}
                </x-slot>
                <x-slot name="headerEnd">
                    @if ($this->selected->status !== \App\Enums\ConversationStatus::Closed)
                        <x-filament::button color="gray" size="sm" wire:click="closeConversation" wire:confirm="Tandai percakapan ini selesai?">Selesai</x-filament::button>
                    @endif
                </x-slot>

                <div class="max-h-[55vh] space-y-3 overflow-y-auto pr-1" x-data x-init="$el.scrollTop = $el.scrollHeight">
                    @foreach ($this->selected->messages as $message)
                        @php($fromCustomer = $message->sender_type === \App\Enums\MessageSender::Customer)
                        <div wire:key="msg-{{ $message->id }}" @class(['flex', 'justify-end' => ! $fromCustomer])>
                            <div @class([
                                'max-w-[80%] whitespace-pre-line rounded-xl px-3.5 py-2 text-sm',
                                'bg-gray-100 text-gray-950 dark:bg-white/10 dark:text-white' => $fromCustomer,
                                'bg-primary-600 text-white' => $message->sender_type === \App\Enums\MessageSender::Admin,
                                'bg-gray-50 text-gray-600 ring-1 ring-gray-200 dark:bg-white/5 dark:text-gray-300 dark:ring-white/10' => $message->sender_type === \App\Enums\MessageSender::Bot,
                            ])>
                                <span class="mb-0.5 block text-[11px] opacity-70">
                                    {{ match ($message->sender_type) { \App\Enums\MessageSender::Customer => 'Customer', \App\Enums\MessageSender::Bot => 'Chatbot', \App\Enums\MessageSender::Admin => 'Admin' } }}
                                    · {{ $message->created_at->format('d M H:i') }}
                                </span>
                                {{ $message->body }}
                            </div>
                        </div>
                    @endforeach
                </div>

                @if ($this->selected->status !== \App\Enums\ConversationStatus::Closed && $this->selected->user_id)
                    <form wire:submit="sendReply" class="mt-4 space-y-2">
                        <textarea wire:model="reply" rows="3" maxlength="1000" placeholder="Tulis balasan"
                            class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white"></textarea>
                        @error('reply') <p class="text-sm text-danger-600">{{ $message }}</p> @enderror
                        <x-filament::button type="submit">Kirim balasan</x-filament::button>
                    </form>
                @elseif (! $this->selected->user_id)
                    <p class="mt-4 text-sm text-gray-500">Percakapan tamu hanya dijawab chatbot. Tamu perlu masuk untuk ngobrol dengan admin.</p>
                @endif
            @else
                <p class="py-12 text-center text-sm text-gray-500">Pilih percakapan di sebelah kiri.</p>
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
