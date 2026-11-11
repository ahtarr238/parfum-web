<?php

namespace App\Livewire;

use App\Enums\ScentFamily;
use App\Models\Brand;
use App\Models\Category;
use App\Models\ProductVariant;
use App\Queries\CatalogQuery;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Catalog extends Component
{
    use WithPagination;

    public const PER_PAGE = 24;

    #[Url(except: '')]
    public string $q = '';

    #[Url(as: 'kategori', except: '')]
    public string $category = '';

    /** @var list<string> */
    #[Url(as: 'brand', except: [])]
    public array $brands = [];

    /** @var list<string> */
    #[Url(as: 'aroma', except: [])]
    public array $scents = [];

    /** @var list<string> */
    #[Url(as: 'ukuran', except: [])]
    public array $sizes = [];

    #[Url(except: '')]
    public string $min = '';

    #[Url(except: '')]
    public string $max = '';

    #[Url(as: 'urut', except: 'terbaru')]
    public string $sort = 'terbaru';

    /** Kategori/brand dari URL rute (/kategori/{slug}, /brand/{slug}) tidak bisa diubah dari filter. */
    public ?int $lockedCategoryId = null;

    public ?int $lockedBrandId = null;

    public function mount(?Category $category = null, ?Brand $brand = null): void
    {
        if ($category?->exists) {
            $this->lockedCategoryId = $category->id;
            $this->category = '';
        }

        if ($brand?->exists) {
            $this->lockedBrandId = $brand->id;
            $this->brands = [];
        }
    }

    public function updating(string $property): void
    {
        if ($property !== 'paginators') {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset('q', 'brands', 'scents', 'sizes', 'min', 'max', 'sort');

        if (! $this->lockedCategoryId) {
            $this->category = '';
        }

        $this->resetPage();
    }

    #[Computed]
    public function lockedCategory(): ?Category
    {
        return $this->lockedCategoryId ? Category::find($this->lockedCategoryId) : null;
    }

    #[Computed]
    public function lockedBrand(): ?Brand
    {
        return $this->lockedBrandId ? Brand::withCount('products')->find($this->lockedBrandId) : null;
    }

    #[Computed]
    public function query(): CatalogQuery
    {
        return (new CatalogQuery(
            search: $this->q,
            category: $this->lockedCategory?->slug ?? ($this->category ?: null),
            brands: $this->lockedBrand ? [$this->lockedBrand->slug] : $this->brands,
            scents: $this->scents,
            sizes: $this->sizes,
            minPrice: $this->priceValue($this->min),
            maxPrice: $this->priceValue($this->max),
        ))->sorted($this->sort);
    }

    #[Computed]
    public function activeFilterCount(): int
    {
        return count($this->brands) + count($this->scents) + count($this->sizes)
            + ($this->category && ! $this->lockedCategoryId ? 1 : 0)
            + ($this->min !== '' ? 1 : 0) + ($this->max !== '' ? 1 : 0);
    }

    public function render(): View
    {
        $title = $this->lockedBrand?->name
            ?? ($this->lockedCategory ? 'Parfum '.mb_strtolower($this->lockedCategory->name) : 'Semua parfum');

        return view('livewire.catalog', [
            'products' => $this->query->builder()->paginate(self::PER_PAGE),
            'categories' => Category::orderBy('id')->get(['id', 'name', 'slug']),
            'brandOptions' => Brand::orderBy('name')->get(['id', 'name', 'slug']),
            'scentOptions' => ScentFamily::cases(),
            'sizeOptions' => ProductVariant::where('is_decant', false)->distinct()->orderBy('size_ml')->pluck('size_ml'),
            'heading' => $this->q !== '' ? "Hasil untuk “{$this->q}”" : $title,
        ])->title($title);
    }

    private function priceValue(string $value): ?int
    {
        $digits = preg_replace('/\D/', '', $value);

        return $digits === '' ? null : min((int) $digits, 100_000_000);
    }
}
