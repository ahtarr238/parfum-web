# PRD E-Commerce Toko Parfum

Nov 11, 2026 · @itsupport

## Ringkasan & Tujuan

Toko parfum online dengan 14 fitur inti, ongkir otomatis lewat RajaOngkir, pembayaran lewat Midtrans, dan chat customer–admin dengan chatbot FAQ. Dibangun dengan Laravel (sisi customer), Filament (panel admin), dan MySQL dalam satu project.

Catatan penting: RajaOngkir hanya untuk menghitung ongkos kirim, bukan pembayaran. Pembayaran ditangani Midtrans (VA bank, QRIS, e-wallet).

**Tujuan:**

- Customer bisa cari parfum berdasarkan aroma, brand, dan ukuran, lalu checkout sampai bayar tanpa bantuan admin.
- Ongkir dan status pembayaran terhitung otomatis, admin cukup kemas dan input resi.
- Pertanyaan umum dijawab chatbot, sisanya dialihkan ke admin di panel yang sama.
- Biaya operasional tahun pertama di bawah Rp1.000.000.

## Pengguna & Peran

| Peran | Siapa | Akses |
| --- | --- | --- |
| Guest | Pengunjung belum login | Lihat katalog, cari, filter, detail produk, chatbot FAQ |
| Customer | Pembeli terdaftar | Semua akses guest + keranjang, wishlist, checkout, bayar, riwayat pesanan, review, chat ke admin |
| Admin | Pemilik/staf toko | Panel Filament di `/admin`: produk, stok, pesanan, resi, voucher, review, FAQ chatbot, inbox chat, laporan |

Admin dibedakan dari customer lewat kolom `role` di tabel `users`. Hanya role `admin` yang bisa masuk panel Filament.

## Ruang Lingkup

**Masuk scope (versi 1):**

- 14 fitur di bagian berikutnya.
- Satu toko, satu gudang (alamat asal pengiriman tetap).
- Pengiriman domestik Indonesia, mata uang Rupiah, bahasa Indonesia.
- Web responsif (desktop dan mobile browser).

**Di luar scope:**

- Aplikasi mobile native (Android/iOS).
- Multi-vendor / marketplace.
- Chatbot berbasis AI (versi 1 pakai rule-based FAQ).
- Integrasi marketplace lain (Shopee, Tokopedia) dan sinkron stok.
- Program poin/membership dan affiliate.

## 14 Fitur

Fitur 7, 8, dan 10 adalah fitur wajib dari klien. Prioritas P0 = harus ada saat launch, P1 = boleh menyusul di minggu terakhir.

| # | Fitur | User story | Acceptance criteria | Prioritas |
| --- | --- | --- | --- | --- |
| 1 | Auth & profil | Sebagai customer, saya ingin daftar dan login agar bisa belanja dan melihat pesanan saya. | Register, login, logout, lupa password via email; tanpa verifikasi email (dihapus atas keputusan pemilik, 11 Nov 2026: akun baru langsung bisa belanja); kelola profil dan beberapa alamat dengan satu alamat utama. | P0 |
| 2 | Katalog & kategori | Sebagai pengunjung, saya ingin melihat parfum per kategori agar cepat menemukan yang cocok. | Kategori pria, wanita, unisex; halaman brand; produk nonaktif atau stok 0 ditandai "Habis". | P0 |
| 3 | Search & filter | Sebagai pengunjung, saya ingin mencari dan memfilter parfum berdasarkan aroma. | Search nama/brand; filter aroma (woody, floral, fresh, oriental, gourmand), brand, ukuran, rentang harga; sort terbaru, termurah, terlaris. | P0 |
| 4 | Detail produk & varian | Sebagai pengunjung, saya ingin melihat detail aroma dan memilih ukuran. | Galeri foto; notes top/middle/base; varian ukuran (mis. 30ml, 50ml, 100ml, decant) dengan harga, stok, dan berat masing-masing. | P0 |
| 5 | Keranjang | Sebagai customer, saya ingin menyimpan beberapa produk sebelum bayar. | Tambah, ubah jumlah, hapus; jumlah tidak boleh melebihi stok; subtotal dan total berat tampil. | P0 |
| 6 | Wishlist | Sebagai customer, saya ingin menyimpan parfum incaran untuk dibeli nanti. | Tambah/hapus dari halaman produk; pindah ke keranjang dari halaman wishlist. | P1 |
| 7 | Checkout + ongkir RajaOngkir | Sebagai customer, saya ingin tahu ongkir sebelum bayar. | Pilih alamat; ongkir dihitung dari total berat lewat API RajaOngkir; pilih kurir dan layanan (JNE, J&T, SiCepat, dll); hasil di-cache per tujuan+berat. | P0 |
| 8 | Pembayaran Midtrans | Sebagai customer, saya ingin bayar dengan VA, QRIS, atau e-wallet. | Midtrans Snap popup; status order diperbarui otomatis via webhook (notification URL) dengan validasi signature; order kedaluwarsa setelah 24 jam tanpa bayar dan stok dikembalikan. | P0 |
| 9 | Pesanan & tracking | Sebagai customer, saya ingin memantau status pesanan sampai diterima. | Status: Menunggu Bayar, Dibayar, Dikemas, Dikirim, Selesai, Dibatalkan; nomor resi tampil; tombol "Pesanan Diterima". | P0 |
| 10 | Chat & chatbot | Sebagai customer, saya ingin bertanya dan langsung dapat jawaban. | Widget chat di semua halaman; chatbot mencocokkan keyword ke tabel FAQ; jika tidak cocok atau customer pilih "Bicara dengan admin", percakapan masuk inbox admin; pesan baru muncul tanpa reload (polling 3–5 detik). | P0 |
| 11 | Review & rating | Sebagai customer, saya ingin memberi ulasan parfum yang sudah saya beli. | Hanya untuk order berstatus Selesai; bintang 1–5 + teks; satu review per item order; admin bisa menyembunyikan review. | P1 |
| 12 | Voucher | Sebagai customer, saya ingin memakai kode promo. | Tipe potongan nominal atau persen; minimal belanja; kuota; tanggal berlaku; satu voucher per order. | P1 |
| 13 | Notifikasi email | Sebagai customer, saya ingin dapat konfirmasi lewat email. | Email invoice saat order dibuat, pembayaran berhasil, dan pesanan dikirim (dengan resi); dikirim lewat queue. | P1 |
| 14 | Admin panel Filament | Sebagai admin, saya ingin mengelola toko dari satu panel. | Resource: produk & varian, kategori, brand, pesanan (update status, input resi), customer, voucher, review, FAQ, inbox chat; dashboard: omzet, jumlah order, produk terlaris, stok menipis. | P0 |

## Alur Utama

Status order hanya berubah menjadi Dibayar lewat webhook Midtrans, bukan dari halaman sukses di browser, supaya tidak bisa dipalsukan.

[embedded content: alur order dan alur chat]

Order yang tidak dibayar dalam 24 jam dibatalkan otomatis oleh scheduler dan stoknya dikembalikan. Di chat, customer selalu bisa melewati chatbot dengan tombol "Bicara dengan admin".

## Wireframe

Lima layar utama dalam versi low-fidelity: tata letak dan isi, bukan warna atau font final. Teks seperti "Nama Parfum" dan "Rp xxx.xxx" adalah placeholder.

### 1. Katalog

[embedded content: wireframe katalog · desktop]

Filter langsung memperbarui grid tanpa reload (Livewire). Produk dengan stok 0 tetap tampil dengan label "Habis". Di mobile, sidebar filter berubah jadi tombol "Filter" yang membuka panel dari bawah.

### 2. Detail Produk

[embedded content: wireframe detail produk · desktop]

Memilih ukuran mengganti harga, stok, dan berat yang dipakai untuk ongkir. Di bawah notes ada daftar ulasan pembeli (fitur 11). Contoh notes di gambar hanya ilustrasi.

### 3. Checkout

[embedded content: wireframe checkout · desktop]

Daftar kurir dan ongkir diambil dari RajaOngkir setiap kali alamat berubah. Tombol "Bayar Sekarang" membuat order, mengunci stok, lalu membuka popup Midtrans Snap.

### 4. Chat & Chatbot

[embedded content: wireframe chat · widget customer dan inbox admin]

Tombol cepat ("Cara order", "Ongkir", "Produk ori?") diambil dari tabel FAQ. Percakapan masuk tab "Menunggu" di inbox admin begitu chatbot tidak menemukan jawaban atau customer menekan "Bicara dengan admin".

### 5. Dashboard Admin (Filament)

[embedded content: wireframe dashboard admin Filament]

Kartu statistik dan grafik memakai widget bawaan Filament (StatsOverview dan Chart). Tombol "Input resi" mengubah status pesanan menjadi Dikirim dan memicu email ke customer.

## Arsitektur & Tech Stack

Sisi customer dan panel admin berada dalam satu project Laravel dan satu database, jadi model dan logika bisnis cukup ditulis sekali.

[embedded content: arsitektur sistem · 1 aplikasi, 4 layanan luar]

Semua panggilan ke RajaOngkir dan Midtrans lewat service class, sehingga controller dan halaman Filament tidak bergantung langsung pada API pihak ketiga.

| Lapisan | Teknologi |
| --- | --- |
| Backend | Laravel 11 / 12, PHP 8.2+ |
| Sisi customer | Blade, Livewire 3, Tailwind CSS, Alpine.js |
| Panel admin | Filament 3 di `/admin` |
| Database | MySQL 8 |
| Ongkir | RajaOngkir API (lewat HTTP client Laravel) |
| Pembayaran | Midtrans Snap (`midtrans/midtrans-php`) |
| Chat | Livewire `wire:poll` 3–5 detik; opsi Laravel Reverb jika pindah ke VPS |
| Queue dan jadwal | Driver database + cron `schedule:run` tiap menit |
| Hosting | Shared hosting dengan SSH, Composer, cron |

## Skema Database (MySQL)

16 tabel inti, di luar tabel bawaan Laravel (sessions, jobs, cache, password_reset_tokens).

| Tabel | Kolom penting | Relasi |
| --- | --- | --- |
| users | name, email, password, phone, role (admin/customer) | punya banyak addresses, orders, reviews |
| addresses | label, recipient, phone, province_id, city_id, district_id, detail, postal_code, is_default | milik users |
| categories | name, slug | punya banyak products |
| brands | name, slug, logo | punya banyak products |
| products | name, slug, description, gender, scent_family, top_notes, middle_notes, base_notes, images (json), is_active | milik categories, brands; punya banyak product_variants |
| product_variants | size_ml, sku, price, stock, weight_gram | milik products |
| carts | user_id, product_variant_id, qty | milik users |
| wishlists | user_id, product_id | milik users |
| orders | code, user_id, status, subtotal, shipping_cost, discount, total, courier, courier_service, tracking_number, address_snapshot (json), voucher_id, expires_at | milik users; punya banyak order_items, satu payment |
| order_items | order_id, product_variant_id, product_name, size_ml, price, qty | milik orders |
| payments | order_id, midtrans_order_id, snap_token, payment_type, status, paid_at, raw_response (json) | milik orders |
| vouchers | code, type (fixed/percent), value, min_purchase, quota, used, starts_at, ends_at | dipakai orders |
| reviews | user_id, product_id, order_item_id, rating, comment, is_visible | milik users, products |
| conversations | user_id, status (bot/waiting_admin/open/closed), last_message_at | milik users; punya banyak messages |
| messages | conversation_id, sender_type (customer/bot/admin), sender_id, body, read_at | milik conversations |
| faqs | question, keywords, answer, is_active | dipakai chatbot |

Harga, nama produk, dan alamat disalin (snapshot) ke order supaya riwayat pesanan tidak berubah saat data produk diedit.

## Kebutuhan Non-Fungsional

- **Keamanan:** HTTPS wajib; validasi signature webhook Midtrans; API key RajaOngkir dan Midtrans hanya di `.env`; CSRF dan rate limit di login dan chat; panel `/admin` hanya untuk role admin.
- **Integritas stok:** stok dikurangi saat order dibuat dalam database transaction dengan row lock, dikembalikan saat order kedaluwarsa atau dibatalkan.
- **Performa:** halaman katalog dimuat di bawah 3 detik di koneksi 4G; gambar dikompres (WebP) dan di-lazy-load; hasil ongkir dan daftar kota di-cache.
- **Kompatibilitas hosting:** harus jalan di shared hosting (PHP 8.2+, MySQL 8, cron). Queue pakai driver database yang diproses cron `schedule:run` tiap menit; tanpa proses yang harus jalan terus.
- **Responsif:** nyaman dipakai di layar mobile 360px ke atas.
- **Backup:** backup database harian otomatis (fitur hosting atau `spatie/laravel-backup`).

## Budget & Timeline

Biaya operasional tahun pertama diperkirakan Rp450–800 ribu, masih di bawah budget Rp1 juta. Angka di bawah adalah perkiraan kasar per November 2026; cek harga terbaru di masing-masing penyedia.

| Pos | Perkiraan biaya | Catatan |
| --- | --- | --- |
| Domain .com | Rp150–200 ribu/tahun | Alternatif .my.id sekitar Rp15 ribu |
| Shared hosting | Rp300–600 ribu/tahun | Wajib PHP 8.2+, MySQL 8, SSH, Composer, cron |
| SSL | Rp0 | Let's Encrypt dari hosting |
| Email transaksi | Rp0 | SMTP bawaan hosting |
| Midtrans | Rp0 biaya bulanan | Potongan per transaksi sesuai metode bayar |
| RajaOngkir | Cek paket terbaru | Pastikan kuota API cukup untuk volume checkout |

Timeline mengasumsikan satu developer full-time.

[embedded content: roadmap pengembangan · 7 fase, 8 minggu]

Fitur wajib (ongkir, pembayaran, chat) dikerjakan berurutan di minggu 4–6 setelah katalog siap, karena ketiganya bergantung pada data produk dan order.

## Risiko, Asumsi & Metrik Sukses

| Risiko | Dampak | Mitigasi |
| --- | --- | --- |
| Paket/harga API RajaOngkir berubah (layanannya sekarang di bawah Komerce) | Ongkir gagal dihitung | Cek paket terbaru sebelum mulai; bungkus API di satu service class agar mudah diganti; cache hasil ongkir |
| Webhook Midtrans gagal sampai ke server | Order terbayar tapi status masih "Menunggu Bayar" | Tombol "Cek status" di admin yang memanggil API status Midtrans; log semua notifikasi |
| Shared hosting tidak mendukung websocket | Chat tidak realtime | Pakai polling Livewire; migrasi ke Laravel Reverb jika pindah ke VPS |
| Chatbot tidak memahami pertanyaan | Customer frustrasi | Tombol "Bicara dengan admin" selalu tersedia; admin menambah FAQ dari pertanyaan yang sering masuk |
| Budget pengembangan Rp1 juta terlalu kecil untuk 14 fitur jika memakai jasa developer | Proyek molor atau kualitas turun | Kerjakan P0 dulu; P1 menyusul |

**Asumsi:** satu admin aktif; katalog awal di bawah 200 varian; Midtrans dan RajaOngkir sudah terdaftar atas nama toko; aset foto produk disediakan pemilik toko.

**Metrik sukses 3 bulan pertama:**

- Minimal 95% order terbayar berubah status otomatis tanpa intervensi admin.
- Minimal 50% chat terjawab chatbot tanpa diteruskan ke admin.
- Tingkat penyelesaian checkout (mulai checkout sampai bayar) minimal 30%.
- Nol kasus stok minus (overselling).

## Prompt Setup AI Agent

Proyek dikerjakan dengan Claude Code + plugin Superpowers sebagai alur kerja (brainstorm, plan, TDD, review), ditambah skill anti-slop untuk UI dan teks. Perintah instalasi di bawah ditulis dari ingatan dan bisa sudah berubah, jadi cocokkan dulu dengan README repo masing-masing.

### 1. Instalasi

1. Install Claude Code (panduan di [docs.claude.com](https://docs.claude.com)), lalu buka terminal di folder project.
2. Pasang Superpowers dari dalam Claude Code:
   1. `/plugin marketplace add obra/superpowers-marketplace`
   2. `/plugin install superpowers@superpowers-marketplace`
3. Pasang skill unslop dari sumber yang kamu pakai ke `.claude/skills/unslop/`. Nama dan repo-nya tidak bisa gw verifikasi, jadi pastikan sendiri. Alternatif yang mirip fungsinya: skill `frontend-design` dari repo `anthropics/skills`.
4. Opsional: Laravel Boost (`composer require laravel/boost --dev` lalu `php artisan boost:install`) supaya agent bisa baca dokumentasi Laravel, Filament, dan Livewire sesuai versi yang terpasang.
5. Export doc ini sebagai Markdown dan simpan di `docs/PRD.md`.
6. Buat file `CLAUDE.md` di root project dengan isi di bawah.

### 2. Isi CLAUDE.md

```markdown
# Toko Parfum - Aturan Project

Baca docs/PRD.md sebelum mengerjakan apa pun. PRD adalah sumber kebenaran untuk fitur, skema database, dan prioritas (P0 dulu, baru P1).

## Stack
- Laravel 11/12, PHP 8.2+, MySQL 8
- Sisi customer: Blade + Livewire 3 + Tailwind + Alpine
- Panel admin: Filament 3 di /admin (satu project, satu database)
- Ongkir: RajaOngkir lewat app/Services/ShippingService.php
- Pembayaran: Midtrans Snap lewat app/Services/PaymentService.php
- Chatbot: rule-based dari tabel faqs lewat app/Services/ChatbotService.php
- Test: Pest

## Alur kerja (wajib, pakai skill Superpowers)
1. Fitur baru: brainstorming dulu, tanya kalau ada yang ambigu, jangan menebak.
2. Tulis rencana dengan writing-plans, simpan di docs/plans/, tunggu persetujuan saya.
3. Kerjakan dengan test-driven-development: test gagal dulu, baru kode.
4. Bug: pakai systematic-debugging, cari akar masalah sebelum mengubah kode.
5. Sebelum bilang selesai: verification-before-completion, jalankan php artisan test dan tunjukkan hasilnya.
6. Satu fitur = satu branch + commit kecil dengan pesan jelas.

## UI dan teks (pakai skill unslop)
- Hindari tampilan template AI: gradien ungu, emoji di tombol, kartu dengan bayangan berlebihan, copy seperti "Elevate your scent journey".
- Nuansa toko parfum: bersih, banyak ruang kosong, foto produk jadi fokus.
- Semua teks UI dalam bahasa Indonesia yang natural dan singkat.
- Ikuti wireframe di PRD bagian Wireframe.

## Batasan teknis
- Target shared hosting: tidak ada proses yang jalan terus. Queue pakai driver database, diproses lewat scheduler (cron schedule:run tiap menit). Chat realtime pakai Livewire wire:poll, bukan Reverb.
- Stok dikurangi di dalam DB transaction dengan lockForUpdate.
- Status order Dibayar HANYA dari webhook Midtrans yang signature-nya valid.
- API key hanya di .env, jangan pernah di-commit.
- Harga, nama produk, dan alamat disalin ke order (snapshot).

## Perintah
- php artisan test
- ./vendor/bin/pint
- npm run dev / npm run build
```

### 3. Prompt kickoff (pesan pertama ke agent)

```text
Baca CLAUDE.md dan docs/PRD.md sampai habis. Lalu:
1. Ringkas pemahamanmu tentang project ini dalam 10 poin.
2. Daftar pertanyaan atau bagian PRD yang ambigu.
3. Pakai skill brainstorming dan writing-plans untuk membuat rencana Fase 1 (setup dan fondasi) sesuai roadmap di PRD. Simpan di docs/plans/fase-1.md.
Jangan menulis kode sebelum saya setujui rencananya.
```

### 4. Prompt per fase

Kirim satu per satu setelah fase sebelumnya lolos test dan sudah kamu cek.

```text
Fase 1: Install Laravel, Filament 3, Livewire, Tailwind, Pest. Buat migrasi dan model untuk 16 tabel di PRD bagian Skema Database beserta relasinya dan factory-nya. Buat auth customer (fitur 1) dan batasi /admin hanya untuk role admin. Ikuti alur TDD.
```

```text
Fase 2: Kerjakan fitur 2, 3, 4 dan resource Filament untuk kategori, brand, produk, varian. Halaman katalog dan detail produk ikuti wireframe 1 dan 2. Pakai skill unslop saat membuat tampilan.
```

```text
Fase 3: Kerjakan fitur 5, 6, 7. Buat ShippingService untuk RajaOngkir (daftar provinsi/kota, hitung ongkir dari total berat, cache hasil). Test pakai HTTP fake, jangan memanggil API asli di test. Halaman checkout ikuti wireframe 3.
```

```text
Fase 4: Kerjakan fitur 8, 9, 13. Buat PaymentService untuk Midtrans Snap (sandbox), endpoint webhook dengan validasi signature, scheduler untuk membatalkan order lewat 24 jam dan mengembalikan stok. Tambah aksi Input resi di resource Pesanan Filament.
```

```text
Fase 5: Kerjakan fitur 10. Widget chat Livewire di semua halaman, ChatbotService yang mencocokkan keyword ke tabel faqs, tombol Bicara dengan admin, dan custom page Inbox Chat di Filament. Ikuti wireframe 4 dan alur chat di PRD.
```

```text
Fase 6: Kerjakan fitur 11, 12, dan dashboard Filament (wireframe 5). Lalu jalankan uji end-to-end alur belanja sampai Selesai dengan Midtrans sandbox, dan laporkan hasilnya.
```

### 5. Prompt pakai ulang

```text
Bug: Ada masalah: [jelaskan]. Pakai systematic-debugging. Temukan akar masalahnya, tulis test yang mereproduksi bug, baru perbaiki.
```

```text
Review: Pakai requesting-code-review untuk mereview perubahan di branch ini terhadap PRD dan CLAUDE.md. Fokus pada keamanan webhook, integritas stok, dan query N+1.
```

```text
Poles UI: Pakai skill unslop untuk mereview semua halaman customer. Daftar bagian yang terlihat generik atau seperti template AI, usulkan perbaikannya, lalu kerjakan setelah saya setujui.
```
