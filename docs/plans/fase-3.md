# Fase 3: Keranjang, Wishlist, Checkout + Ongkir — Plan

**Goal:** Fitur 5, 6, 7 dari PRD, plus UI kelola alamat yang dipindah dari Fase 1.

## Keputusan

1. **`ShippingService` membungkus API RajaOngkir (Komerce)** di `https://rajaongkir.komerce.id/api/v1`. Endpoint yang dipakai: `GET destination/province`, `GET destination/city/{province_id}`, `GET destination/district/{city_id}`, dan `POST calculate/district/domestic-cost`. Semuanya memakai header `key`.
2. **Cache:** daftar wilayah disimpan 30 hari. Hasil ongkir disimpan 6 jam per kombinasi (tujuan, berat), sesuai PRD.
3. **Mode lokal tanpa API key:** jika `RAJAONGKIR_FAKE=true` dan aplikasi berjalan di environment `local`, respons RajaOngkir dipalsukan lewat `Http::fake` berisi data contoh (`App\Support\FakeRajaOngkir`). Jalur kode di `ShippingService` tetap sama. Test selalu memakai `Http::fake`.
4. **Kurir:** daftar kurir diatur lewat `RAJAONGKIR_COURIERS` (default `jne:jnt:sicepat:pos:tiki`), dan asal pengiriman lewat `RAJAONGKIR_ORIGIN` (ID kecamatan gudang).
5. **Checkout mewajibkan email terverifikasi** (middleware `verified`).
6. **Ongkir dihitung ulang di server saat order dibuat,** dan nilai dari browser tidak dipercaya.
7. **Pembayaran Midtrans ditunda atas permintaan pengguna.** "Bayar Sekarang" membuat order (stok dikunci dengan `lockForUpdate`, snapshot, keranjang dikosongkan), lalu customer diarahkan ke halaman pesanan dengan status Menunggu Bayar. `PaymentService` dikerjakan belakangan.
8. **Dari wishlist, "Pindah ke keranjang"** memasukkan varian termurah yang masih ada stoknya, lalu menghapus produk itu dari wishlist.

## Task

1. `ShippingService` dan `FakeRajaOngkir`, diuji dengan `Http::fake`.
2. Halaman alamat dan komponen `AddressForm` dengan dropdown provinsi, kota, dan kecamatan.
3. Halaman keranjang: ubah jumlah, hapus, subtotal, dan total berat.
4. Halaman wishlist: hapus dan pindah ke keranjang.
5. `OrderService::place()` dan halaman checkout (pilih alamat, pilih kurir, ringkasan, tombol "Bayar Sekarang").
