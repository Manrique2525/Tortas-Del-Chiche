<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;

class DashboardController extends Controller
{
    public function index()
    {
        $products = Product::orderBy('sort_order')->get();
        $totalActive = $products->where('active', true)->count();
        $totalInactive = $products->where('active', false)->count();

        return view('admin.dashboard', compact('products', 'totalActive', 'totalInactive'));
    }
}
