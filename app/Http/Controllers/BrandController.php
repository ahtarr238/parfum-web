<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Contracts\View\View;

class BrandController extends Controller
{
    public function index(): View
    {
        return view('brands.index', [
            'brands' => Brand::withCount(['products' => fn ($q) => $q->where('is_active', true)])
                ->orderBy('name')
                ->get(),
        ]);
    }
}
