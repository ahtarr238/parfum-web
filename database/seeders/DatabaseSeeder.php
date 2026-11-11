<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Data dev. Admin TIDAK dibuat di sini: pakai `php artisan app:create-admin`.
     *
     * Customer uji: customer@tokoparfum.test / password
     */
    public function run(): void
    {
        $this->call([
            CategorySeeder::class,
            CatalogDemoSeeder::class,
            FaqSeeder::class,
        ]);

        User::updateOrCreate(['email' => 'customer@tokoparfum.test'], [
            'name' => 'Customer Uji',
            'phone' => '081234567890',
            'password' => 'password',
        ])->forceFill(['email_verified_at' => now()])->save();
    }
}
