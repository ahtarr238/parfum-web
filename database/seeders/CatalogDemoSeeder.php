<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Katalog contoh untuk development. Nama brand dan produk fiktif.
 */
class CatalogDemoSeeder extends Seeder
{
    public function run(): void
    {
        $brands = collect(['Rimba Atelier', 'Senja Parfumerie', 'Kala Scent', 'Saka & Co'])
            ->mapWithKeys(fn (string $name) => [$name => Brand::updateOrCreate(['slug' => Str::slug($name)], ['name' => $name])]);

        $categories = Category::pluck('id', 'slug');

        // [nama, brand, kategori, aroma, top, middle, base, [[ml, decant, harga, stok]...]]
        $products = [
            ['Hutan Pagi', 'Rimba Atelier', 'pria', 'woody', 'Bergamot, Cardamom', 'Cedarwood, Vetiver', 'Sandalwood, Musk', [[30, false, 189000, 12], [50, false, 279000, 8], [100, false, 459000, 5]]],
            ['Akar Wangi', 'Rimba Atelier', 'unisex', 'woody', 'Grapefruit', 'Akar wangi, Patchouli', 'Amber, Oud', [[10, true, 59000, 30], [50, false, 329000, 6], [100, false, 539000, 0]]],
            ['Kopi Tubruk', 'Rimba Atelier', 'pria', 'gourmand', 'Kopi, Pink pepper', 'Kayu manis, Tembakau', 'Vanila, Tonka', [[30, false, 199000, 10], [50, false, 299000, 7]]],
            ['Melati Senja', 'Senja Parfumerie', 'wanita', 'floral', 'Pear, Mandarin', 'Melati, Sedap malam', 'Musk putih, Cedar', [[30, false, 219000, 15], [50, false, 319000, 9], [100, false, 499000, 4]]],
            ['Mawar Kebun', 'Senja Parfumerie', 'wanita', 'floral', 'Lychee, Bergamot', 'Mawar damask, Peony', 'Patchouli, Musk', [[10, true, 65000, 20], [50, false, 349000, 0]]],
            ['Kenanga', 'Senja Parfumerie', 'wanita', 'floral', 'Neroli', 'Kenanga, Ylang-ylang', 'Benzoin, Vanila', [[30, false, 209000, 11], [50, false, 309000, 5], [100, false, 489000, 2]]],
            ['Hujan Pertama', 'Kala Scent', 'unisex', 'fresh', 'Lemon, Mint', 'Daun teh, Petrichor', 'Musk, Ambroxan', [[30, false, 179000, 18], [50, false, 259000, 14], [100, false, 419000, 6]]],
            ['Laut Biru', 'Kala Scent', 'pria', 'fresh', 'Sea salt, Lime', 'Lavender, Geranium', 'Driftwood, Musk', [[50, false, 269000, 10], [100, false, 429000, 3]]],
            ['Teh Melati', 'Kala Scent', 'unisex', 'fresh', 'Bergamot, Teh hijau', 'Melati', 'Musk', [[10, true, 49000, 25], [30, false, 169000, 9], [50, false, 249000, 0]]],
            ['Malam Rempah', 'Saka & Co', 'pria', 'oriental', 'Cengkih, Pala', 'Kayu manis, Mawar', 'Oud, Amber', [[30, false, 239000, 7], [50, false, 359000, 4], [100, false, 579000, 1]]],
            ['Gula Aren', 'Saka & Co', 'wanita', 'gourmand', 'Caramel, Pir', 'Praline, Iris', 'Vanila, Tonka', [[30, false, 199000, 13], [50, false, 299000, 8]]],
            ['Dupa Keraton', 'Saka & Co', 'unisex', 'oriental', 'Saffron', 'Kemenyan, Mawar', 'Oud, Labdanum', [[10, true, 79000, 15], [50, false, 389000, 3], [100, false, 629000, 2]]],
        ];

        foreach ($products as [$name, $brand, $category, $scent, $top, $middle, $base, $variants]) {
            $product = Product::updateOrCreate(['slug' => Str::slug($name)], [
                'category_id' => $categories[$category],
                'brand_id' => $brands[$brand]->id,
                'name' => $name,
                'description' => "{$name} dari {$brand}. Parfum dengan karakter {$scent} yang cocok dipakai sehari-hari.",
                'scent_family' => $scent,
                'top_notes' => $top,
                'middle_notes' => $middle,
                'base_notes' => $base,
                'images' => [],
                'is_active' => true,
            ]);

            foreach ($variants as [$ml, $decant, $price, $stock]) {
                $sku = Str::upper(Str::slug($name, '')).'-'.($decant ? 'D' : '').$ml;
                $product->variants()->updateOrCreate(['sku' => $sku], [
                    'size_ml' => $ml,
                    'is_decant' => $decant,
                    'price' => $price,
                    'stock' => $stock,
                    'weight_gram' => $decant ? 50 : $ml * 3 + 150,
                ]);
            }
        }
    }
}
