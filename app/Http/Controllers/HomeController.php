<?php

namespace App\Http\Controllers;

use App\Enums\ScentFamily;
use App\Models\Category;
use App\Queries\CatalogQuery;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('home', [
            'newest' => (new CatalogQuery)->sorted('terbaru')->builder()->where('is_active', true)->limit(8)->get(),
            'bestsellers' => (new CatalogQuery)->sorted('terlaris')->builder()->where('is_active', true)->limit(4)->get(),
            'categories' => Category::withCount(['products' => fn ($q) => $q->where('is_active', true)])->orderBy('id')->get(),
            'scents' => ScentFamily::cases(),
        ]);
    }
}
