<div class="fixed bottom-4 right-4 z-40 sm:bottom-6 sm:right-6">
    @if ($open)
        <section wire:poll.4s="poll" class="mb-3 flex h-[min(560px,calc(100vh-7rem))] w-[calc(100vw-2rem)] max-w-sm flex-col overflow-hidden rounded-2xl border border-line bg-paper-card shadow-float" aria-label="Chat">
            <header class="flex items-center justify-between border-b border-line px-4 py-3">
                <div>
                    <p class="text-[15px] font-medium">Tanya kami</p>
                    <p class="meta flex items-center gap-1.5">
                        @if ($withAdmin)
                            <span class="size-1.5 rounded-full bg-green-600"></span> Terhubung ke admin
                        @else
                            Dijawab otomatis, admin siap bantu
                        @endif
                    </p>
                </div>
                <button type="button" wire:click="$set('open', false)" class="inline-flex size-8 items-center justify-center rounded-lg text-ink-2 hover:bg-ink/5" aria-label="Tutup chat">
                    <x-lucide-x class="size-4" stroke-width="1.5" />
                </button>
            </header>

            <div class="flex-1 space-y-3 overflow-y-auto px-4 py-4" x-data x-init="$el.scrollTop = $el.scrollHeight" x-effect="$nextTick(() => $el.scrollTop = $el.scrollHeight)" aria-live="polite">
                <div class="max-w-[85%] rounded-2xl rounded-tl-md bg-paper-raised px-3.5 py-2.5 text-sm leading-6">
                    Halo, ada yang bisa dibantu? Pilih pertanyaan di bawah atau tulis sendiri.
                </div>

                @foreach ($this->conversation?->messages ?? [] as $message)
                    @php($mine = $message->sender_type === \App\Enums\MessageSender::Customer)
                    <div wire:key="m-{{ $message->id }}" @class(['flex', 'justify-end' => $mine])>
                        <div @class([
                            'max-w-[85%] whitespace-pre-line rounded-2xl px-3.5 py-2.5 text-sm leading-6',
                            'rounded-tr-md bg-ink text-paper-card' => $mine,
                            'rounded-tl-md bg-paper-raised' => ! $mine,
                        ])>
                            @if ($message->sender_type === \App\Enums\MessageSender::Admin)
                                <span class="mb-0.5 block text-xs font-medium text-ember">Admin</span>
                            @endif
                            {{ $message->body }}
                            @if (! auth()->check() && $message->body === \App\Services\ChatService::LOGIN_MESSAGE)
                                <a href="{{ route('login') }}" class="mt-1 block font-medium text-ember underline underline-offset-4">Masuk</a>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            @unless ($withAdmin)
                <div class="flex flex-wrap gap-2 border-t border-line px-4 pt-3">
                    @foreach ($quickQuestions as $faq)
                        <button type="button" wire:click="ask(@js($faq->question))" class="h-8 rounded-lg border border-line px-3 text-[13px] text-ink-2 hover:border-ink/30 hover:text-ink">{{ $faq->question }}</button>
                    @endforeach
                    <button type="button" wire:click="talkToAdmin" class="h-8 rounded-lg border border-ink/20 px-3 text-[13px] text-ink hover:bg-ink/5">Bicara dengan admin</button>
                </div>
            @endunless

            <form wire:submit="send" class="flex items-end gap-2 px-4 py-3">
                <label for="chat-body" class="sr-only">Pesan</label>
                <textarea id="chat-body" wire:model="body" rows="1" maxlength="1000" placeholder="Tulis pesan"
                          x-on:keydown.enter="if (! $event.shiftKey) { $event.preventDefault(); $wire.send() }"
                          class="field max-h-28 min-h-10 resize-none py-2 text-sm"></textarea>
                <button type="submit" class="inline-flex size-10 shrink-0 items-center justify-center rounded-lg bg-ink text-paper-card hover:bg-ink/90" aria-label="Kirim">
                    <x-lucide-send-horizontal class="size-4" stroke-width="1.5" />
                </button>
            </form>
            @error('body') <p class="px-4 pb-3 text-xs text-red-700">{{ $message }}</p> @enderror
        </section>
    @endif

    <div class="flex justify-end">
        <button type="button" wire:click="$toggle('open')"
                class="inline-flex h-12 items-center gap-2 rounded-full bg-ink px-5 text-[15px] text-paper-card shadow-float transition-transform duration-150 ease-out hover:bg-ink/90 active:scale-[0.97]"
                aria-expanded="{{ $open ? 'true' : 'false' }}">
            @if ($open)
                <x-lucide-chevron-down class="size-[18px]" stroke-width="1.5" /> Tutup
            @else
                <x-lucide-message-circle class="size-[18px]" stroke-width="1.5" /> Chat
            @endif
        </button>
    </div>
</div>
