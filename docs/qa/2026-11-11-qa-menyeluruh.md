# Laporan QA Menyeluruh: 11 November 2026

Cakupan: semua fitur PRD kecuali pembayaran Midtrans (fitur 8), yang ditunda atas permintaan pemilik.

## Hasil otomatis

| Pemeriksaan | Hasil |
| --- | --- |
| `php artisan test` (MySQL `parfum_test`) | 234 lulus, 722 assertion |
| `./vendor/bin/pint --test` | lulus |
| `npm run build` | sukses. CSS 54 KB (9,5 KB gzip), JS 59 KB (22 KB gzip) |
| `migrate:fresh --seed` | sukses |
| Scheduler | `orders:expire` dan `queue:work --stop-when-empty` tiap menit |
| Rahasia di repo | `.env` dan password DB tidak ada di git |

## Uji end-to-end di browser (localhost)

Alur yang dijalankan memakai akun customer baru:
1. Daftar akun, lalu verifikasi lewat link email.
2. Filter Floral di katalog. Grid berubah tanpa reload dan URL ikut berubah.
3. Di detail produk, pilih ukuran 100ml. Harga, stok, dan berat ikut berganti.
4. Tambah 2 barang ke keranjang. Angka keranjang di header langsung jadi 2.
5. Tambah alamat dengan dropdown provinsi → kota → kecamatan dari data contoh RajaOngkir.
6. Di checkout, pilih kurir SiCepat dan pakai voucher WANGI10. Total Rp998.000 + Rp11.500 − Rp99.800 = **Rp909.700** sudah benar.
7. Tekan Bayar Sekarang. Pesanan Menunggu Bayar terbentuk, stok berkurang 2, kuota voucher terpakai 1, dan keranjang kosong.
8. Pembayaran disimulasikan lewat `OrderService::markPaid()`, fungsi yang nanti dipanggil webhook.
9. Admin menekan "Tandai dikemas", lalu "Input resi". Status jadi Dikirim dan resi tampil di sisi customer.
10. Customer menekan "Pesanan Diterima" dan menulis ulasan bintang 5. Ulasan tampil di halaman produk.
11. Chat:
    - Tombol cepat "Produk ori?" dijawab chatbot.
    - Pertanyaan "bungkus kado" diteruskan ke admin.
    - Admin membalas dari Inbox, dan balasannya muncul di widget customer lewat polling.
12. Email yang terkirim lewat queue, masing-masing satu kali: verifikasi, invoice, pembayaran diterima, dan dikirim (berisi resi).
13. Dashboard admin: omzet Rp909.700, 1 pesanan perlu diproses, 1 chat menunggu, produk terlaris, dan stok menipis. Semua angkanya sesuai.

## Uji balapan (proses PHP paralel sungguhan)

| Skenario | Hasil |
| --- | --- |
| 2 customer berebut 1 stok terakhir | 1 berhasil, 1 ditolak "stok tidak cukup", stok akhir 0 |
| 1 customer klik Bayar dua kali bersamaan | 1 pesanan, request kedua ditolak "Keranjang kosong" |

## Mobile (360–375px)

- Tidak ada overflow horizontal di 10 halaman (beranda, katalog, detail, keranjang, pesanan, detail pesanan, alamat, wishlist, brand, profil).
- Filter katalog jadi tombol "Filter" yang membuka panel dari bawah. Panel ini menerapkan filter dan tombolnya menampilkan jumlah hasil.

## Performa

- Server: 4–14 query per halaman tanpa N+1 (`preventLazyLoading` aktif di dev dan test). Waktu proses dalam aplikasi 20–160 ms.
- Lewat `php artisan serve` di Windows tanpa OPcache, respons sekitar 700 ms. Selisihnya adalah biaya boot PHP per request, yang akan jauh turun di hosting dengan OPcache.
- Gambar produk dikonversi ke WebP (lebar maksimal 1600, kualitas 80) dan memakai `loading="lazy"`.

## Temuan review independen (semuanya sudah diperbaiki, masing-masing dengan test regresi)

1. **Tinggi:** menyimpan produk di Filament menimpa stok yang berubah karena pesanan. Kolom stok varian lama sekarang terkunci di form, dan perubahan stok dilakukan lewat aksi "Ubah stok" yang atomik dengan `lockForUpdate`.
2. **Tinggi:** proses kedaluwarsa dan pembatalan oleh customer bisa membatalkan order yang baru dibayar. Sekarang ada `expire()` dan `cancelUnpaid()` yang memeriksa ulang status dan batas waktu setelah row lock diambil.
3. **Sedang:** dobel submit bisa membuat dua order. Sekarang baris user dikunci dan keranjang dibaca ulang di dalam transaction.
4. **Rendah:** panggilan API RajaOngkir terjadi saat row lock stok masih dipegang. Ongkir sekarang dihitung sebelum transaction.
5. **Rendah:** webhook yang dikirim ulang memicu email ganda. `markPaid` sekarang idempotent.
6. **Rendah:** `expireOverdue` bisa melewatkan baris saat datanya banyak. Sekarang memakai `lazyById`.
7. **Rendah:** menghapus akun yang punya riwayat pesanan menghasilkan error 500. Sekarang ditolak dengan pesan yang jelas.

## Polish dari QA (sudah dikerjakan)

- Email verifikasi dan reset password dikirim lewat queue, sehingga pendaftaran tidak lagi menunggu sekitar 5 detik.
- Nama wilayah dirapikan dengan tetap mempertahankan singkatan, misalnya "DKI Jakarta" dan "Kota Bandung".
- Setelah admin membalas chat, Inbox pindah ke tab "Dibalas" dengan percakapan tetap terpilih.
- Halaman auth dan profil memakai token desain toko: tanpa indigo, tanpa label huruf besar, dan teks tanpa tanda seru.
- Ukuran CSS turun dari 106 KB ke 54 KB.

## Belum dikerjakan / perlu keputusan

- **Pembayaran Midtrans (fitur 8):** `PaymentService`, webhook dengan validasi signature, tombol Bayar Snap, dan aksi "Cek status" di admin. Hook `markPaid()` sudah siap.
- **API key RajaOngkir asli dan ID kecamatan gudang (`RAJAONGKIR_ORIGIN`).** Format respons API produksi perlu dicek sekali dengan key asli, karena saat ini yang teruji adalah data contoh dan `Http::fake`.
- **Gambar wireframe** belum ada di repo. Tata letak mengikuti deskripsi teks di PRD.
- **Foto produk asli.** Selama belum ada, produk ditampilkan dengan ilustrasi botol SVG.
