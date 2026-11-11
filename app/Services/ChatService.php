<?php

namespace App\Services;

use App\Enums\ConversationStatus;
use App\Enums\MessageSender;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ChatService
{
    public const MAX_LENGTH = 1000;

    public const HANDOFF_MESSAGE = 'Pesanmu sudah kami teruskan ke admin. Balasannya muncul di sini, biasanya dalam jam kerja (09.00–17.00 WIB).';

    public const LOGIN_MESSAGE = 'Maaf, aku belum punya jawabannya. Masuk dulu supaya bisa ngobrol langsung dengan admin.';

    public function __construct(private readonly ChatbotService $bot) {}

    /**
     * Percakapan aktif untuk user (atau guest lewat token sesi). Percakapan guest dengan token
     * yang sama dipindah ke akun saat user sudah login.
     */
    public function conversationFor(?User $user, string $token): Conversation
    {
        $active = fn ($q) => $q->where('status', '!=', ConversationStatus::Closed);

        if ($user) {
            $existing = Conversation::where('user_id', $user->id)->where($active)->latest('id')->first();

            if ($existing) {
                return $existing;
            }

            $guest = Conversation::whereNull('user_id')->where('session_id', $token)->where($active)->latest('id')->first();

            if ($guest) {
                $guest->update(['user_id' => $user->id]);

                return $guest;
            }

            return Conversation::create(['user_id' => $user->id, 'session_id' => $token]);
        }

        return Conversation::whereNull('user_id')->where('session_id', $token)->where($active)->latest('id')->first()
            ?? Conversation::create(['session_id' => $token]);
    }

    public function customerSends(Conversation $conversation, string $body, ?User $user): Message
    {
        $body = $this->clean($body);

        return DB::transaction(function () use ($conversation, $body, $user) {
            $message = $this->post($conversation, MessageSender::Customer, $body, $user?->id);

            if ($conversation->status === ConversationStatus::Bot) {
                $faq = $this->bot->match($body);

                if ($faq) {
                    $this->post($conversation, MessageSender::Bot, $faq->answer);
                } elseif ($user) {
                    $this->handOff($conversation);
                } else {
                    $this->post($conversation, MessageSender::Bot, self::LOGIN_MESSAGE);
                }
            }

            return $message;
        });
    }

    /**
     * Tombol "Bicara dengan admin": selalu bisa melewati chatbot (khusus customer yang login).
     */
    public function requestAdmin(Conversation $conversation, User $user): void
    {
        if ($conversation->user_id !== $user->id) {
            throw new InvalidArgumentException('Percakapan bukan milik user ini.');
        }

        if ($conversation->status === ConversationStatus::Bot) {
            $this->handOff($conversation);
        }
    }

    public function adminReplies(Conversation $conversation, User $admin, string $body): Message
    {
        $body = $this->clean($body);

        return DB::transaction(function () use ($conversation, $admin, $body) {
            $conversation->messages()
                ->where('sender_type', MessageSender::Customer)
                ->whereNull('read_at')
                ->update(['read_at' => now()]);

            $message = $this->post($conversation, MessageSender::Admin, $body, $admin->id);
            $conversation->update(['status' => ConversationStatus::Open]);

            return $message;
        });
    }

    public function close(Conversation $conversation): void
    {
        $conversation->update(['status' => ConversationStatus::Closed]);
    }

    private function handOff(Conversation $conversation): void
    {
        $this->post($conversation, MessageSender::Bot, self::HANDOFF_MESSAGE);
        $conversation->update(['status' => ConversationStatus::WaitingAdmin]);
    }

    private function post(Conversation $conversation, MessageSender $sender, string $body, ?int $senderId = null): Message
    {
        $message = $conversation->messages()->create([
            'sender_type' => $sender,
            'sender_id' => $senderId,
            'body' => $body,
        ]);

        $conversation->update(['last_message_at' => now()]);

        return $message;
    }

    private function clean(string $body): string
    {
        $body = trim($body);

        if ($body === '' || mb_strlen($body) > self::MAX_LENGTH) {
            throw new InvalidArgumentException('Pesan harus 1–'.self::MAX_LENGTH.' karakter.');
        }

        return $body;
    }
}
