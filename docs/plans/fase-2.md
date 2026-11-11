# Fase 2: Katalog, Search & Filter, Detail Produk — Plan

> Pengguna meminta eksekusi berlanjut tanpa jeda persetujuan per fase ("lanjutkan terus"). Rencana ini dicatat untuk jejak keputusan.

**Goal:** Fitur 2, 3, 4 dari PRD plus resource Filament untuk kategori, brand, produk, dan varian.

**Spec:** `docs/PRD.md` fitur 2–4, wireframe 1–2 (deskripsi teks), Kebutuhan Non-Fungsional (WebP, lazy-load, < 3 detik).

## Arah desain (skill unslop)

- **Produk:** toko parfum untuk pembeli Indonesia yang mencari parfum berdasarkan aroma, brand, dan ukuran.
- **Karakter:** tenang, hangat, teliti.
- **Dunia:** meja peracik parfum, dengan kertas blotter, botol kaca, dan label kecil.
- **Momen visual:** foto botol. Selama foto belum ada, botol kaca digambar dalam SVG dengan warna isi sesuai keluarga aroma. Halaman detail menampilkan susunan notes top, middle, dan base.
- **Ciri khas:** titik warna keluarga aroma (woody, floral, fresh, oriental, gourmand) yang muncul di filter, kartu, dan detail.
- **Token:** neutral ramp warm light (bg #f7f4ee, card #fdfbf7, teks #1f1b16/#5e554a/#7b7266), aksen #8a4b1f (ember), font Instrument Sans + Instrument Serif (hanya untuk headline besar), icon lucide 16–18px stroke 1.5.

## Keputusan

1. **Produk nonaktif tetap tampil dengan label "Habis" dan tidak bisa dibeli,** sesuai teks PRD. Produk yang semua variannya stok 0 juga diberi label "Habis".
2. **Harga di kartu katalog adalah "mulai" dari varian termurah.** Filter harga juga memakai harga varian termurah.
3. **Filter ukuran berisi daftar `size_ml` unik dan satu opsi "Decant".**
4. **Urutan "Terlaris" dihitung dari jumlah `order_items.qty`** pada order berstatus Dibayar, Dikemas, Dikirim, atau Selesai.
5. **URL:** `/katalog` (query string `q`, `kategori`, `brand[]`, `aroma[]`, `ukuran[]`, `min`, `max`, `urut`), `/kategori/{slug}`, `/brand`, `/brand/{slug}`, dan `/parfum/{slug}`.
6. **Gambar produk dikonversi ke WebP (lebar maksimal 1600px, kualitas 80) saat diunggah di Filament,** memakai GD. Gambar di storefront memakai `loading="lazy"`.
7. **Logika query katalog ada di `App\Queries\CatalogQuery`,** sehingga komponen Livewire hanya meneruskan filter.

## Task

1. **Layout toko:** font, warna, header (navigasi kategori, search, ikon keranjang/wishlist/akun), dan footer. Breeze dashboard diganti jadi halaman "Akun saya".
2. **`CatalogQuery`:** search nama/brand, filter kategori/brand/aroma/ukuran/harga, dan urutan terbaru/termurah/terlaris. Masing-masing diuji.
3. **Livewire `Catalog`:** grid, sidebar filter (di mobile jadi tombol "Filter" yang membuka panel dari bawah), sinkron URL, dan label "Habis".
4. **Livewire `ProductDetail`:** galeri, notes, dan pilihan varian yang mengganti harga, stok, dan berat. Tombol keranjang dihubungkan di Fase 3.
5. **Beranda dan halaman brand.**
6. **Filament:** resource Kategori, Brand, dan Produk (repeater varian, unggah gambar ke WebP), plus test akses admin.
