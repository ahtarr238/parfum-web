<?php

namespace App\Mail;

class OrderPlacedMail extends OrderMail
{
    protected function subjectLine(): string
    {
        return "Invoice {$this->order->code}";
    }

    protected function intro(): string
    {
        return 'Terima kasih, pesananmu sudah kami terima. Selesaikan pembayaran sebelum '
            .$this->order->expires_at->translatedFormat('j F Y, H:i').' WIB supaya stok tidak dilepas.';
    }
}
