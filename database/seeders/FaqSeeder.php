<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            [
                'Cara order',
                'cara order,cara pesan,cara beli,order,pesan,beli,checkout',
                'Pilih parfum dan ukurannya, klik "Tambah ke keranjang", lalu buka keranjang dan klik "Checkout". Pilih alamat dan kurir, lalu bayar lewat VA, QRIS, atau e-wallet.',
            ],
            [
                'Ongkir',
                'ongkir,ongkos kirim,biaya kirim,kurir,pengiriman,jne,jnt,j&t,sicepat',
                'Ongkir dihitung otomatis saat checkout berdasarkan alamat tujuan dan berat pesanan. Kamu bisa memilih kurir dan layanan yang paling cocok.',
            ],
            [
                'Produk ori?',
                'ori,original,asli,palsu,kw,authentic',
                'Semua parfum yang kami jual original. Decant diambil langsung dari botol original dan dikemas di atomizer baru.',
            ],
            [
                'Berapa lama pengiriman?',
                'berapa lama,lama kirim,estimasi,sampai kapan,kapan sampai,dikirim kapan',
                'Pesanan dikemas maksimal 1x24 jam hari kerja setelah pembayaran diterima. Lama perjalanan mengikuti estimasi kurir yang kamu pilih.',
            ],
            [
                'Apa itu decant?',
                'decant,atomizer,sample,tester,ukuran kecil',
                'Decant adalah parfum original yang dipindahkan ke botol kecil (misalnya 10ml). Cocok untuk mencoba aroma sebelum membeli botol penuh.',
            ],
        ];

        foreach ($faqs as [$question, $keywords, $answer]) {
            Faq::updateOrCreate(['question' => $question], [
                'keywords' => $keywords,
                'answer' => $answer,
                'is_active' => true,
            ]);
        }
    }
}
