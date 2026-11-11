<?php

use App\Filament\Resources\BrandResource;
use App\Filament\Resources\CategoryResource;
use App\Filament\Resources\ProductResource;
use App\Filament\Resources\ProductResource\Pages\CreateProduct;
use App\Filament\Resources\ProductResource\Pages\EditProduct;
use App\Filament\Resources\ProductResource\Pages\ListProducts;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Support\ImageOptimizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
});

it('admin bisa membuka halaman resource katalog', function () {
    $this->actingAs($this->admin);

    $this->get(ProductResource::getUrl('index'))->assertOk();
    $this->get(ProductResource::getUrl('create'))->assertOk();
    $this->get(CategoryResource::getUrl('index'))->assertOk();
    $this->get(BrandResource::getUrl('index'))->assertOk();
});

it('customer tidak bisa membuka resource admin', function () {
    $this->actingAs(User::factory()->create())
        ->get(ProductResource::getUrl('index'))
        ->assertForbidden();
});

it('daftar produk menampilkan produk', function () {
    $products = Product::factory()->count(3)->create();

    Livewire::actingAs($this->admin)->test(ListProducts::class)
        ->assertCanSeeTableRecords($products);
});

it('admin membuat produk beserta varian', function () {
    $category = Category::factory()->create();
    $brand = Brand::factory()->create();

    Livewire::actingAs($this->admin)->test(CreateProduct::class)
        ->fillForm([
            'name' => 'Hutan Pagi',
            'slug' => 'hutan-pagi',
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'scent_family' => 'woody',
            'description' => 'Aroma kayu pagi.',
            'top_notes' => 'Bergamot',
            'is_active' => true,
        ])
        ->set('data.variants', [
            ['size_ml' => 50, 'is_decant' => false, 'sku' => 'HP-50', 'price' => 279000, 'stock' => 8, 'weight_gram' => 300],
            ['size_ml' => 10, 'is_decant' => true, 'sku' => 'HP-D10', 'price' => 59000, 'stock' => 20, 'weight_gram' => 50],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $product = Product::firstWhere('slug', 'hutan-pagi');

    expect($product)->not->toBeNull()
        ->and($product->variants)->toHaveCount(2)
        ->and($product->variants->firstWhere('sku', 'HP-50')->price)->toBe(279000);
});

it('menolak varian tanpa harga dan sku ganda', function () {
    ProductVariant::factory()->create(['sku' => 'SAMA']);

    Livewire::actingAs($this->admin)->test(CreateProduct::class)
        ->fillForm([
            'name' => 'X',
            'slug' => 'x',
            'category_id' => Category::factory()->create()->id,
            'brand_id' => Brand::factory()->create()->id,
            'scent_family' => 'fresh',
            'description' => 'x',
        ])
        ->set('data.variants', [
            ['size_ml' => 50, 'is_decant' => false, 'sku' => 'SAMA', 'price' => null, 'stock' => 1, 'weight_gram' => 300],
        ])
        ->call('create')
        ->assertHasFormErrors();

    expect(Product::where('slug', 'x')->exists())->toBeFalse();
});

it('admin mengubah harga varian lewat form, stok tidak ikut tertimpa', function () {
    $product = Product::factory()->create();
    $variant = ProductVariant::factory()->for($product)->create(['stock' => 1, 'price' => 100000]);

    $component = Livewire::actingAs($this->admin)->test(EditProduct::class, ['record' => $product->slug]);
    $key = array_key_first($component->get('data.variants'));

    $component->set("data.variants.{$key}.price", 125000)
        ->set("data.variants.{$key}.stock", 25)
        ->call('save')
        ->assertHasNoFormErrors();

    expect($variant->fresh()->price)->toBe(125000)
        ->and($variant->fresh()->stock)->toBe(1);
});

it('ImageOptimizer mengubah gambar ke WebP dan membatasi lebar', function () {
    Storage::fake('public');
    $file = UploadedFile::fake()->image('botol.jpg', 2400, 3000);

    $path = app(ImageOptimizer::class)->storeAsWebp($file, 'products');

    expect($path)->toEndWith('.webp');
    Storage::disk('public')->assertExists($path);

    [$width] = getimagesizefromstring(Storage::disk('public')->get($path));
    expect($width)->toBe(1600);
});
