# Fase 1: Setup & Fondasi — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Project Laravel yang jalan lokal dengan 16 tabel PRD (migrasi, model, relasi, factory), auth customer (fitur 1), dan `/admin` Filament yang hanya bisa dibuka role admin.

**Architecture:** Satu project Laravel 12 di root repo. Auth customer memakai Laravel Breeze stack Livewire (Volt) + Pest. Panel admin Filament 3 di `/admin` memakai tabel `users` yang sama; akses dibatasi lewat `User::canAccessPanel()`. Semua uang disimpan sebagai integer Rupiah. Status dan tipe memakai PHP backed enum.

**Tech Stack:** PHP 8.2+, Laravel ^12.0, Livewire 3 + Volt, Laravel Breeze ^2.4, Filament ^3.3, Tailwind 3 (dari Breeze), Alpine, MySQL 8.4, Pest, laravel-lang/common (terjemahan id).

**Spec:** `docs/PRD.md` (fitur 1, Pengguna & Peran, Skema Database, Kebutuhan Non-Fungsional). Aturan kerja: `CLAUDE.md`.

## Keputusan yang saya ambil (cek ini saat review)

Pengguna meminta langsung jalan tanpa sesi tanya-jawab, jadi poin-poin ini adalah keputusan saya, bukan keputusan pengguna:

1. **Laravel 12, bukan 13.** Laravel 13 butuh PHP 8.3, padahal PRD hanya menjamin hosting PHP 8.2+. Catatan: dukungan keamanan Laravel 12 berakhir sekitar Februari 2027, jadi upgrade ke 13 begitu hosting sudah pasti PHP 8.3+.
2. **Filament 3.3, sesuai PRD.** Versi 3.3.57 mendukung Laravel 12.
3. **Auth memakai Breeze (Livewire/Volt).** Breeze sudah menyediakan register, login, logout, lupa password, verifikasi email, dan profil. Kolom `phone` ditambahkan di form profil, bukan di form register.
4. **Kolom `products.gender` dihapus.** Kategori Pria/Wanita/Unisex jadi satu-satunya sumber gender, supaya tidak ada dua sumber data yang bisa bertentangan.
5. **Kolom `product_variants.is_decant` (boolean) ditambahkan.** Decant tetap punya `size_ml` (misalnya 5 atau 10), dan flag ini dipakai untuk label "Decant".
6. **Kolom `province_name`, `city_name`, `district_name` ditambahkan ke `addresses`.** ID-nya dipakai RajaOngkir (`/destination/province`, `/destination/city/{province_id}`, `/destination/district/{city_id}`), sedangkan namanya dipakai untuk tampilan dan snapshot order.
7. **Halaman kelola alamat dipindah ke Fase 3.** Dropdown provinsi/kota/kecamatan butuh `ShippingService`. Di Fase 1 yang dibuat hanya tabel dan aturan alamat utama (`AddressBook`) beserta test-nya.
8. **`conversations.user_id` dibuat nullable dan ada kolom `session_id`.** Dengan begitu guest bisa memakai chatbot. Tombol "Bicara dengan admin" akan mewajibkan login di Fase 5 (sesuai tabel peran: chat ke admin hanya untuk customer).
9. **Test dijalankan di database MySQL `parfum_test`, bukan SQLite.** Tujuannya supaya perilaku JSON, unsigned, dan row lock sama dengan produksi.
10. **Admin dibuat lewat command `php artisan app:create-admin`.** Tidak ada seeder berisi password admin.
11. **Nilai enum status disimpan dalam bahasa Inggris dengan label bahasa Indonesia.** Contohnya `pending_payment` dengan label "Menunggu Bayar".

## Global Constraints

- PHP 8.2+; `composer.json` → `"config": {"platform": {"php": "8.2.0"}}` agar dependency tetap kompatibel dengan shared hosting.
- MySQL 8; `DB_CONNECTION=mysql`, database `parfum` (dev) dan `parfum_test` (test).
- `QUEUE_CONNECTION=database`, `CACHE_STORE=database`, `SESSION_DRIVER=database`. Tidak ada proses yang harus jalan terus.
- `APP_LOCALE=id`, `APP_FALLBACK_LOCALE=en`, `APP_FAKER_LOCALE=id_ID`, `APP_TIMEZONE=Asia/Jakarta`.
- Semua teks UI dalam bahasa Indonesia.
- Uang: `unsignedInteger` Rupiah tanpa desimal. Stok dan qty: `unsignedInteger`.
- Role: kolom `users.role`, nilai `admin` / `customer`, default `customer`. Hanya `admin` yang bisa masuk `/admin`.
- `.env` tidak boleh di-commit. `.env.example` hanya berisi placeholder.
- Setiap commit diakhiri baris `Co-Authored-By: Claude Opus 5.5 <noreply@anthropic.com>`.
- Branch kerja: `fase-1/fondasi` dari `main`.

## Review Focus

1. **Register dengan field `role=admin` di payload** harus tetap membuat customer, karena `role` tidak boleh bisa diisi lewat mass-assignment. Diuji di Task 2.
2. **Customer yang sudah verifikasi email membuka `/admin`** harus mendapat 403. Akses admin hanya ditentukan oleh `role`. Diuji di Task 2.
3. **Saat alamat utama dihapus,** alamat terbaru yang tersisa harus otomatis jadi utama, sehingga user tidak pernah punya dua alamat utama atau nol alamat utama selama masih punya alamat. Diuji di Task 4.
4. **Saat varian atau produk dihapus,** `order_items` harus tetap ada dengan snapshot nama, ukuran, dan harga (FK `nullOnDelete`). Diuji di Task 5.
5. **Stok yang dikurangi sampai di bawah 0** harus ditolak database (kolom unsigned, MySQL strict). Ini fondasi untuk target "nol overselling". Diuji di Task 3.

---

### Task 0: Lingkungan lokal (dikerjakan pengguna, bukan agent)

PHP dan MySQL belum terpasang (folder XAMPP tidak punya `php.exe` maupun binary MySQL). Langkah-langkah ini memasang software ke sistem, jadi pengguna yang menjalankannya.

- [ ] **Step 1: Pasang PHP 8.3 + Composer** lewat installer resmi Laravel (php.new). Jalankan di PowerShell sebagai user biasa:
  `Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://php.new/install/windows/8.3'))`
- [ ] **Step 2: Pasang MySQL 8.4** lewat `winget install Oracle.MySQL`, lalu jalankan MySQL Configurator. Pilih port 3306, jalankan sebagai Windows service, dan isi password root.
- [ ] **Step 3: Buat database dan user** di `mysql -u root -p`:
  `CREATE DATABASE parfum CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE DATABASE parfum_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE USER 'parfum'@'localhost' IDENTIFIED BY '<password-lokal>'; GRANT ALL ON parfum.* TO 'parfum'@'localhost'; GRANT ALL ON parfum_test.* TO 'parfum'@'localhost';`
- [ ] **Step 4: Verifikasi** di terminal baru:
  Jalankan `php -v`, `composer -V`, `mysql --version`, dan `php -m`.
  Expected: PHP ≥ 8.2, Composer 2.x, MySQL 8.4. `php -m` harus memuat `intl`, `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `zip`, `gd`.

---

### Task 1: Scaffold Laravel 12 + Breeze + Pest + konfigurasi dasar

**Files:**
- Create: seluruh skeleton Laravel di root repo (pertahankan `CLAUDE.md`, `docs/`)
- Modify: `composer.json`, `.env.example`, `phpunit.xml`, `.gitignore`
- Create: `lang/id/*`, `lang/id.json` (dari laravel-lang)
- Test: `tests/Feature/Setup/LocaleTest.php`

**Interfaces:**
- Produces: aplikasi Laravel dengan route Breeze (`login`, `register`, `password.request`, `verification.notice`, `profile`, `dashboard`), Pest siap, koneksi test ke `parfum_test`.

- [ ] **Step 1: Inisialisasi git** dengan menjalankan `git init -b main`, lalu commit `CLAUDE.md` dan `docs/` dengan pesan `docs: tambah PRD dan aturan project`. Setelah itu jalankan `git switch -c fase-1/fondasi`.
- [ ] **Step 2: Buat skeleton di folder sementara lalu pindahkan.** Jalankan `composer create-project laravel/laravel:^12.0 _scaffold`, lalu pindahkan semua isi `_scaffold` (termasuk dotfile) ke root dan hapus `_scaffold`.
- [ ] **Step 3: Pasang Breeze** dengan `composer require laravel/breeze:^2.4 --dev` lalu `php artisan breeze:install livewire --pest --no-interaction`. Breeze memasang Tailwind 3. Kalau `vite.config.js` atau `package.json` masih memuat `@tailwindcss/vite` atau `tailwindcss` v4 dari skeleton, hapus agar hanya tersisa satu Tailwind (v3).
- [ ] **Step 4: Terapkan Global Constraints ke `.env.example` dan `.env`.** Ini mencakup platform PHP 8.2.0 di `composer.json`. Di `phpunit.xml`, set `DB_CONNECTION=mysql` dan `DB_DATABASE=parfum_test`, lalu hapus baris SQLite `:memory:`.
- [ ] **Step 5: Pasang terjemahan** dengan `composer require laravel-lang/common --dev` lalu `php artisan lang:add id`.
- [ ] **Step 6: Tulis test locale yang gagal** di `tests/Feature/Setup/LocaleTest.php`:

```php
it('menampilkan pesan validasi dalam bahasa Indonesia', function () {
    $component = Volt::test('pages.auth.register')->call('register')->assertHasErrors(['email']);
    expect($component->errors()->first('email'))->toContain('wajib diisi');
});

it('memakai zona waktu Jakarta', function () {
    expect(config('app.timezone'))->toBe('Asia/Jakarta');
});
```
- [ ] **Step 7: Jalankan** `php artisan test --filter=LocaleTest`. Expected: FAIL kalau locale atau timezone belum diset, PASS setelah Step 4–5. Kalau sudah langsung PASS, pastikan dulu dengan mengubah `APP_LOCALE=en` sementara bahwa test-nya bisa gagal.
- [ ] **Step 8: Jalankan seluruh suite** dengan `php artisan migrate` lalu `php artisan test`. Expected: semua test bawaan Breeze PASS di MySQL. Lalu jalankan `npm install && npm run build` dan pastikan build sukses tanpa error.
- [ ] **Step 9: Commit** dengan pesan `chore: scaffold Laravel 12, Breeze Livewire, Pest, locale id`. Pastikan `git status` tidak memuat `.env`.

---

### Task 2: Role user, panel Filament, dan command buat admin

**Files:**
- Create: `app/Enums/UserRole.php`, `database/migrations/xxxx_add_role_and_phone_to_users_table.php`, `app/Console/Commands/CreateAdmin.php`, `app/Providers/Filament/AdminPanelProvider.php` (dari installer)
- Modify: `app/Models/User.php`, `database/factories/UserFactory.php`, `resources/views/livewire/profile/update-profile-information-form.blade.php`
- Test: `tests/Feature/Auth/AdminAccessTest.php`, `tests/Feature/Auth/CreateAdminCommandTest.php`, `tests/Feature/Profile/PhoneTest.php`

**Interfaces:**
- Produces: `enum UserRole: string { case Admin = 'admin'; case Customer = 'customer'; }`; `User::isAdmin(): bool`; `User` implements `FilamentUser`, `MustVerifyEmail`; factory state `UserFactory::admin()`; command `app:create-admin`.

- [ ] **Step 1: Pasang Filament** dengan `composer require filament/filament:"^3.3" -W` lalu `php artisan filament:install --panels`. Isi panel id dengan `admin` dan path `admin`.
- [ ] **Step 2: Tulis test yang gagal** di `AdminAccessTest.php`:

```php
it('mengarahkan guest ke login admin', fn () => $this->get('/admin')->assertRedirect('/admin/login'));

it('menolak customer terverifikasi', function () {
    $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
});

it('mengizinkan admin', function () {
    $this->actingAs(User::factory()->admin()->create())->get('/admin')->assertOk();
});

it('register membuat user dengan role customer', function () {
    Volt::test('pages.auth.register')
        ->set('name', 'Budi')->set('email', 'budi@example.test')
        ->set('password', 'rahasia123')->set('password_confirmation', 'rahasia123')
        ->call('register');
    expect(User::firstWhere('email', 'budi@example.test')->role)->toBe(UserRole::Customer);
});

it('tidak bisa mengisi role lewat mass assignment', function () {
    $user = User::create(['name' => 'X', 'email' => 'x@example.test', 'password' => 'p', 'role' => 'admin']);
    expect($user->fresh()->role)->toBe(UserRole::Customer);
});
```

- [ ] **Step 3: Jalankan** `php artisan test --filter=AdminAccessTest`. Expected: FAIL (kolom `role` belum ada).
- [ ] **Step 4: Implementasi.**
  - Migrasi: `role` string(20) default `customer` dengan index, dan `phone` string(20) nullable setelah `email`.
  - `User`: cast `role` ke `UserRole`; `role` **tidak** masuk `$fillable`; `canAccessPanel(Panel $panel): bool` mengembalikan `$this->role === UserRole::Admin`; implement `MustVerifyEmail`.
  - Factory: default `role` customer; state `admin()`.
- [ ] **Step 5: Jalankan test**. Expected: PASS.
- [ ] **Step 6: Tulis test command yang gagal** di `CreateAdminCommandTest.php`:

```php
it('membuat admin terverifikasi', function () {
    $this->artisan('app:create-admin')
        ->expectsQuestion('Nama', 'Admin Toko')
        ->expectsQuestion('Email', 'admin@example.test')
        ->expectsQuestion('Password', 'rahasia123')
        ->assertSuccessful();
    $u = User::firstWhere('email', 'admin@example.test');
    expect($u->role)->toBe(UserRole::Admin)->and($u->email_verified_at)->not->toBeNull();
});

it('menolak email yang sudah dipakai', function () {
    User::factory()->create(['email' => 'admin@example.test']);
    $this->artisan('app:create-admin')
        ->expectsQuestion('Nama', 'A')->expectsQuestion('Email', 'admin@example.test')
        ->expectsQuestion('Password', 'rahasia123')
        ->assertFailed();
});
```

- [ ] **Step 7: Implementasi** `CreateAdmin` (signature `app:create-admin`). Pakai `ask` untuk Nama dan Email, `secret` untuk Password, lalu validasi email unik dan password minimal 8. Set `role` dan `email_verified_at` lewat `forceFill`. Jalankan test. Expected: PASS.
- [ ] **Step 8: Tulis test, lalu implementasi field `phone` di form profil Breeze** (label "No. HP", nullable, maks 20, hanya angka/+). Test di `PhoneTest.php`:

```php
it('menyimpan nomor HP dari halaman profil', function () {
    $user = User::factory()->create();
    Volt::actingAs($user)->test('profile.update-profile-information-form')
        ->set('phone', '081234567890')->call('updateProfileInformation')->assertHasNoErrors();
    expect($user->fresh()->phone)->toBe('081234567890');
});

it('menolak nomor HP berisi huruf', function () {
    Volt::actingAs(User::factory()->create())->test('profile.update-profile-information-form')
        ->set('phone', '08abc')->call('updateProfileInformation')->assertHasErrors(['phone']);
});
```

  Tambahkan `phone` ke `$fillable`.
- [ ] **Step 9: Jalankan** `php artisan test`. Expected: semua PASS.
- [ ] **Step 10: Commit** dengan pesan `feat: role admin/customer, panel Filament /admin, command app:create-admin`.

---

### Task 3: Skema katalog (categories, brands, products, product_variants)

**Files:**
- Create: `app/Enums/ScentFamily.php`; migrasi 4 tabel; model `Category`, `Brand`, `Product`, `ProductVariant`; factory masing-masing; `database/seeders/CategorySeeder.php`
- Test: `tests/Feature/Models/CatalogModelsTest.php`

**Interfaces:**
- Produces:
  - `enum ScentFamily: string { Woody='woody'; Floral='floral'; Fresh='fresh'; Oriental='oriental'; Gourmand='gourmand' }` dengan `label(): string` (Woody, Floral, Fresh, Oriental, Gourmand).
  - `Category hasMany products`; `Brand hasMany products`; `Product belongsTo category, brand; hasMany variants` (method `variants()`); `ProductVariant belongsTo product`.
  - Kolom `products`: `category_id`, `brand_id` (FK restrictOnDelete), `name`, `slug` unique, `description` text, `scent_family` string(20), `top_notes`/`middle_notes`/`base_notes` string(255) nullable (dipisah koma), `images` json, `is_active` bool default true.
  - Kolom `product_variants`: `product_id` (cascadeOnDelete), `size_ml` unsignedSmallInteger, `is_decant` bool default false, `sku` unique, `price` unsignedInteger, `stock` unsignedInteger default 0, `weight_gram` unsignedInteger.
  - `categories`: `name`, `slug` unique. `brands`: `name`, `slug` unique, `logo` nullable.
  - `CategorySeeder` membuat Pria/`pria`, Wanita/`wanita`, Unisex/`unisex` secara idempotent (`updateOrCreate`).

- [ ] **Step 1: Tulis test yang gagal:**

```php
it('menghubungkan produk dengan kategori, brand, dan varian', function () {
    $p = Product::factory()->has(ProductVariant::factory()->count(3), 'variants')->create();
    expect($p->category)->toBeInstanceOf(Category::class)
        ->and($p->brand)->toBeInstanceOf(Brand::class)
        ->and($p->variants)->toHaveCount(3);
});

it('meng-cast images ke array dan scent_family ke enum', function () {
    $p = Product::factory()->create(['images' => ['a.webp', 'b.webp'], 'scent_family' => 'woody']);
    expect($p->fresh()->images)->toBe(['a.webp', 'b.webp'])
        ->and($p->fresh()->scent_family)->toBe(ScentFamily::Woody);
});

it('menolak stok di bawah nol', function () {
    $v = ProductVariant::factory()->create(['stock' => 1]);
    expect(fn () => $v->decrement('stock', 2))->toThrow(QueryException::class);
});

it('menolak slug produk ganda', function () {
    Product::factory()->create(['slug' => 'sama']);
    expect(fn () => Product::factory()->create(['slug' => 'sama']))->toThrow(QueryException::class);
});

it('seeder kategori idempotent', function () {
    $this->seed(CategorySeeder::class);
    $this->seed(CategorySeeder::class);
    expect(Category::pluck('slug')->sort()->values()->all())->toBe(['pria', 'unisex', 'wanita']);
});
```

- [ ] **Step 2: Jalankan** `php artisan test --filter=CatalogModelsTest`. Expected: FAIL (class tidak ada).
- [ ] **Step 3: Implementasi** migrasi, model, enum, factory, dan seeder sesuai blok Interfaces. Factory memakai `fake()->unique()` untuk slug dan sku. Harga factory berada di rentang 50000–3000000 dalam kelipatan 1000.
- [ ] **Step 4: Jalankan test**. Expected: PASS.
- [ ] **Step 5: Commit** dengan pesan `feat: skema katalog produk, brand, kategori, varian`.

---

### Task 4: Data customer (addresses, carts, wishlists) + aturan alamat utama

**Files:**
- Create: migrasi `addresses`, `carts`, `wishlists`; model `Address`, `CartItem` (tabel `carts`), `WishlistItem` (tabel `wishlists`); factory; `app/Services/AddressBook.php`
- Modify: `app/Models/User.php` (relasi)
- Test: `tests/Feature/Models/CustomerDataTest.php`, `tests/Feature/Services/AddressBookTest.php`

**Interfaces:**
- Produces:
  - `addresses`: `user_id` (cascade), `label` string(50), `recipient`, `phone` string(20), `province_id`/`city_id`/`district_id` unsignedInteger, `province_name`/`city_name`/`district_name` string, `detail` text, `postal_code` string(10), `is_default` bool default false.
  - `carts`: `user_id` (cascade), `product_variant_id` (cascade), `qty` unsignedInteger. Unique (`user_id`, `product_variant_id`).
  - `wishlists`: `user_id` (cascade), `product_id` (cascade). Unique (`user_id`, `product_id`).
  - `User::addresses()`, `User::defaultAddress(): HasOne` (where `is_default`), `User::cartItems()`, `User::wishlistItems()`.
  - `AddressBook::add(User $user, array $data): Address`, `AddressBook::makeDefault(Address $address): void`, `AddressBook::remove(Address $address): void`. Ketiganya berjalan dalam `DB::transaction`.

- [ ] **Step 1: Tulis test `AddressBookTest` yang gagal:**

```php
it('alamat pertama otomatis jadi utama', function () {
    $u = User::factory()->create();
    $a = app(AddressBook::class)->add($u, Address::factory()->raw());
    expect($a->is_default)->toBeTrue();
});

it('alamat kedua tidak mengambil status utama', function () {
    $u = User::factory()->create();
    $book = app(AddressBook::class);
    $book->add($u, Address::factory()->raw());
    expect($book->add($u, Address::factory()->raw())->is_default)->toBeFalse();
});

it('makeDefault menyisakan tepat satu alamat utama', function () {
    $u = User::factory()->create();
    $book = app(AddressBook::class);
    $book->add($u, Address::factory()->raw());
    $b = $book->add($u, Address::factory()->raw());
    $book->makeDefault($b);
    expect($u->addresses()->where('is_default', true)->pluck('id')->all())->toBe([$b->id]);
});

it('menghapus alamat utama mempromosikan alamat terbaru', function () {
    $u = User::factory()->create();
    $book = app(AddressBook::class);
    $a = $book->add($u, Address::factory()->raw());
    $this->travel(1)->minutes();
    $b = $book->add($u, Address::factory()->raw());
    $book->remove($a);
    expect($b->fresh()->is_default)->toBeTrue();
});

it('menghapus alamat terakhir tidak error', function () {
    $u = User::factory()->create();
    $book = app(AddressBook::class);
    $book->remove($book->add($u, Address::factory()->raw()));
    expect($u->addresses()->count())->toBe(0);
});
```

- [ ] **Step 2: Tulis test `CustomerDataTest` yang gagal.** Test ini memeriksa relasi user ke `cartItems`/`wishlistItems`, `QueryException` saat ada baris cart ganda untuk varian yang sama, dan wishlist ganda untuk produk yang sama.
- [ ] **Step 3: Jalankan** `php artisan test --filter="AddressBookTest|CustomerDataTest"`. Expected: FAIL.
- [ ] **Step 4: Implementasi** migrasi, model, factory, relasi, dan `AddressBook` sesuai Interfaces. Factory `Address` memakai ID dan nama wilayah palsu yang konsisten (misalnya province_id 1 dengan nama "Jawa Barat").
- [ ] **Step 5: Jalankan test**. Expected: PASS.
- [ ] **Step 6: Commit** dengan pesan `feat: tabel alamat, keranjang, wishlist dan aturan alamat utama`.

---

### Task 5: Skema order (vouchers, orders, order_items, payments)

**Files:**
- Create: `app/Enums/OrderStatus.php`, `app/Enums/VoucherType.php`; migrasi 4 tabel; model `Voucher`, `Order`, `OrderItem`, `Payment`; factory
- Modify: `app/Models/User.php` (`orders()`)
- Test: `tests/Feature/Models/OrderModelsTest.php`

**Interfaces:**
- Produces:
  - `enum OrderStatus: string` dengan case `PendingPayment='pending_payment'`, `Paid='paid'`, `Processing='processing'`, `Shipped='shipped'`, `Completed='completed'`, `Cancelled='cancelled'`, plus `label(): string` yang mengembalikan "Menunggu Bayar", "Dibayar", "Dikemas", "Dikirim", "Selesai", "Dibatalkan".
  - `enum VoucherType: string { Fixed='fixed'; Percent='percent' }`.
  - `vouchers`: `code` unique, `type`, `value` unsignedInteger, `min_purchase` unsignedInteger default 0, `quota` unsignedInteger, `used` unsignedInteger default 0, `starts_at`/`ends_at` datetime.
  - `orders`: `code` string(30) unique, `user_id` (restrictOnDelete), `status` string(20) index default `pending_payment`, `subtotal`/`shipping_cost`/`discount`/`total` unsignedInteger, `courier` string(20) nullable, `courier_service` string(50) nullable, `tracking_number` nullable, `address_snapshot` json, `voucher_id` nullable (nullOnDelete), `expires_at` datetime index.
  - `order_items`: `order_id` (cascade), `product_variant_id` nullable (nullOnDelete), `product_name`, `size_ml` unsignedSmallInteger, `price` unsignedInteger, `qty` unsignedInteger.
  - `payments`: `order_id` unique (cascade), `midtrans_order_id` unique, `snap_token` nullable, `payment_type` nullable, `status` string(30) default `pending`, `paid_at` nullable, `raw_response` json nullable.
  - Relasi: `Order belongsTo user, voucher; hasMany items (OrderItem); hasOne payment`; `OrderItem belongsTo order, variant (ProductVariant)`; `Voucher hasMany orders`; `User hasMany orders`.
  - Cast: `Order::status` → `OrderStatus`, `address_snapshot` → `array`, `expires_at` → `datetime`; `Payment::raw_response` → `array`, `paid_at` → `datetime`.

- [ ] **Step 1: Tulis test yang gagal:**

```php
it('label status sesuai PRD', function () {
    expect(array_map(fn ($s) => $s->label(), OrderStatus::cases()))
        ->toBe(['Menunggu Bayar', 'Dibayar', 'Dikemas', 'Dikirim', 'Selesai', 'Dibatalkan']);
});

it('order baru berstatus menunggu bayar', function () {
    expect(Order::factory()->create()->fresh()->status)->toBe(OrderStatus::PendingPayment);
});

it('item order tetap ada saat varian dihapus', function () {
    $item = OrderItem::factory()->create(['product_name' => 'Nama Parfum', 'size_ml' => 50, 'price' => 350000]);
    $item->variant->product->delete();
    $item->refresh();
    expect($item->product_variant_id)->toBeNull()
        ->and([$item->product_name, $item->size_ml, $item->price])->toBe(['Nama Parfum', 50, 350000]);
});

it('menyimpan snapshot alamat sebagai array', function () {
    $o = Order::factory()->create(['address_snapshot' => ['recipient' => 'Budi', 'city_name' => 'Bandung']]);
    expect($o->fresh()->address_snapshot['city_name'])->toBe('Bandung');
});

it('satu order hanya punya satu payment', function () {
    $o = Order::factory()->create();
    Payment::factory()->for($o)->create();
    expect(fn () => Payment::factory()->for($o)->create())->toThrow(QueryException::class);
});
```

- [ ] **Step 2: Jalankan** `php artisan test --filter=OrderModelsTest`. Expected: FAIL.
- [ ] **Step 3: Implementasi** sesuai Interfaces. Factory `Order` mengisi `code` dengan format `INV-YYYYMMDD-XXXXX` (5 karakter acak huruf besar dan angka) dan `expires_at` = sekarang + 24 jam.
- [ ] **Step 4: Jalankan test**. Expected: PASS.
- [ ] **Step 5: Commit** dengan pesan `feat: skema order, item, payment, voucher`.

---

### Task 6: Skema review, chat, dan FAQ

**Files:**
- Create: `app/Enums/ConversationStatus.php`, `app/Enums/MessageSender.php`; migrasi `reviews`, `conversations`, `messages`, `faqs`; model `Review`, `Conversation`, `Message`, `Faq`; factory
- Modify: `app/Models/User.php` (`reviews()`, `conversations()`), `app/Models/Product.php` (`reviews()`)
- Test: `tests/Feature/Models/EngagementModelsTest.php`

**Interfaces:**
- Produces:
  - `enum ConversationStatus: string { Bot='bot'; WaitingAdmin='waiting_admin'; Open='open'; Closed='closed' }`.
  - `enum MessageSender: string { Customer='customer'; Bot='bot'; Admin='admin' }`.
  - `reviews`: `user_id` (cascade), `product_id` (cascade), `order_item_id` unique (cascade), `rating` unsignedTinyInteger, `comment` text nullable, `is_visible` bool default true.
  - `conversations`: `user_id` nullable (cascade), `session_id` string(100) nullable index, `status` string(20) default `bot`, `last_message_at` datetime nullable index.
  - `messages`: `conversation_id` (cascade), `sender_type` string(20), `sender_id` unsignedBigInteger nullable, `body` text, `read_at` nullable.
  - `faqs`: `question`, `keywords` string(500) (dipisah koma, huruf kecil), `answer` text, `is_active` bool default true.
  - Relasi: `Conversation hasMany messages; belongsTo user`; `Message belongsTo conversation`; `Review belongsTo user, product, orderItem`.

- [ ] **Step 1: Tulis test yang gagal.** Test mencakup: (a) review kedua untuk `order_item_id` yang sama melempar `QueryException`; (b) percakapan guest dengan `user_id` null dan `session_id` terisi tersimpan dengan status `ConversationStatus::Bot`; (c) `$conversation->messages` terurut sesuai urutan insert dan `sender_type` ter-cast ke `MessageSender`; (d) `Product::reviews()` mengembalikan review produk itu saja.
- [ ] **Step 2: Jalankan** `php artisan test --filter=EngagementModelsTest`. Expected: FAIL.
- [ ] **Step 3: Implementasi** sesuai Interfaces.
- [ ] **Step 4: Jalankan test**. Expected: PASS.
- [ ] **Step 5: Commit** dengan pesan `feat: skema review, percakapan, pesan, FAQ`.

---

### Task 7: Seeder data dev + verifikasi akhir Fase 1

**Files:**
- Create: `database/seeders/CatalogDemoSeeder.php`, `database/seeders/FaqSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`
- Test: `tests/Feature/Setup/SeederTest.php`

**Interfaces:**
- Consumes: semua model dan factory dari Task 2–6, `CategorySeeder`.
- Produces: `php artisan migrate:fresh --seed` menghasilkan 3 kategori, 4 brand, 12 produk (masing-masing 2–4 varian, satu varian dengan stok 0 untuk uji label "Habis" di Fase 2), 3 FAQ ("Cara order", "Ongkir", "Produk ori?"), dan satu customer uji terverifikasi. Kredensial customer uji ditulis di `DatabaseSeeder` dan README, tidak di chat.

- [ ] **Step 1: Tulis test yang gagal.** Setelah `$this->seed()`, jumlah kategori = 3, produk = 12, FAQ aktif = 3, ada minimal satu varian dengan `stock = 0`, dan tidak ada user dengan `role = admin` (karena admin hanya dibuat lewat command).
- [ ] **Step 2: Jalankan**. Expected: FAIL.
- [ ] **Step 3: Implementasi** seeder. Teks FAQ dalam bahasa Indonesia, singkat, dan tanpa emoji.
- [ ] **Step 4: Verifikasi akhir.** Jalankan berurutan:
  `php artisan migrate:fresh --seed`, `php artisan test`, `./vendor/bin/pint --test`, `npm run build`.
  Expected: migrasi sukses, semua test PASS, Pint tanpa pelanggaran, build sukses.
- [ ] **Step 5: Cek manual di browser.** Jalankan `php artisan serve` lalu periksa: `/register` berbahasa Indonesia, register berhasil dan diarahkan ke verifikasi email (mail driver `log`), `/admin` menolak customer, dan admin hasil `app:create-admin` bisa masuk dan melihat dashboard Filament.
- [ ] **Step 6: Commit** dengan pesan `chore: seeder data dev dan verifikasi Fase 1`. Tunjukkan output `php artisan test` ke pengguna.

---

## Di luar Fase 1 (sengaja tidak dikerjakan)

- Resource Filament untuk katalog (Fase 2), halaman katalog/detail (Fase 2), UI kelola alamat dan `ShippingService` (Fase 3).
- Cron `schedule:run`, Midtrans, email transaksi (Fase 4), chat (Fase 5).
- Desain UI final. Halaman auth Breeze cukup diterjemahkan dan dirapikan seperlunya; polesan visual dilakukan di Fase 2 dengan skill unslop.
