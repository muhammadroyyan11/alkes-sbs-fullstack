<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $featuredProducts = Product::query()
            ->where('is_active', true)
            ->orderByDesc('stock')
            ->limit(8)
            ->get();

        return view('front.home', compact('featuredProducts'));
    }
}
