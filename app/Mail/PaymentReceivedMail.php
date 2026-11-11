<?php

namespace App\Mail;

class PaymentReceivedMail extends OrderMail
{
    protected function subjectLine(): string
    {
        return "Pembayaran {$this->order->code} diterima";
    }

    protected function intro(): string
    {
        return 'Pembayaranmu sudah kami terima. Pesanan segera dikemas dan nomor resi akan dikirim lewat email.';
    }
}
