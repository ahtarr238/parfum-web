# Fase 5–6: Chat, Review, Voucher, Dashboard — Catatan Keputusan

## Fase 5: Chat & chatbot (fitur 10)

- **`ChatbotService`** mencocokkan kata atau frasa utuh (keyword dan teks pertanyaan FAQ), tanpa peduli huruf besar atau tanda baca. FAQ dengan kecocokan terbanyak yang dipilih, dan frasa yang lebih panjang mendapat skor lebih tinggi.
- **`ChatService`:**
  - Percakapan guest disimpan lewat `session('chat_token')` dan dipindah ke akun setelah login.
  - Pertanyaan yang tidak dikenali dari customer otomatis diteruskan ke admin (status `waiting_admin`). Guest diminta masuk dulu, sesuai tabel peran di PRD.
  - Tombol "Bicara dengan admin" selalu tersedia.
- **Widget** dipasang di layout `app` dan `guest`. `wire:poll.4s` hanya aktif saat panel terbuka. Ada rate limit 15 pesan per menit per user atau IP.
- **Inbox chat Filament** (custom page) punya tab Menunggu, Dibalas, Chatbot, dan Selesai, polling 4 detik, form balas, dan tombol "Selesai".
- **Resource FAQ:** keyword dinormalisasi jadi huruf kecil dan dipisah koma.

## Fase 6: Review, voucher, dashboard (fitur 11, 12, 14)

- **Review** sudah dikerjakan di Fase 4 (`ReviewService`, `ReviewForm`). Di Fase 6 ditambah `ReviewResource` dengan aksi Sembunyikan/Tampilkan.
- **Voucher** sudah dikerjakan di Fase 3 (`VoucherService`). Di Fase 6 ditambah `VoucherResource`: kode disimpan huruf besar, persen maksimal 100, dan tanggal berakhir harus setelah tanggal mulai.
- **`CustomerResource`** bersifat read-only: daftar customer, total belanja, dan 10 pesanan terakhir.
- **Dashboard:** `StoreStats` (omzet bulan ini dibanding periode yang sama bulan lalu, order dibayar, perlu diproses, chat menunggu), `RevenueChart` (30 hari), `TopProductsTable`, dan `LowStockTable` (stok ≤ 5).
- **Omzet** dihitung dari order berstatus Dibayar, Dikemas, Dikirim, atau Selesai, berdasarkan tanggal order dibuat. Setelah Midtrans aktif, sebaiknya diganti ke `payments.paid_at`.
