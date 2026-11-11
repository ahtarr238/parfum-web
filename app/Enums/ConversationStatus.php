<?php

namespace App\Enums;

enum ConversationStatus: string
{
    case Bot = 'bot';
    case WaitingAdmin = 'waiting_admin';
    case Open = 'open';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Bot => 'Chatbot',
            self::WaitingAdmin => 'Menunggu',
            self::Open => 'Dibalas',
            self::Closed => 'Selesai',
        };
    }
}
