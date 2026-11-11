<?php

namespace App\Mail;

class OrderShippedMail extends OrderMail
{
    protected function subjectLine(): string
    {
        return "Pesanan {$this->order->code} sudah dikirim";
    }

    protected function intro(): string
    {
        return 'Pesananmu sudah diserahkan ke kurir. Pakai nomor resi di bawah untuk melacak paketnya.';
    }

    public function showTracking(): bool
    {
        return true;
    }
}
