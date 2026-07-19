<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function index(): JsonResponse
    {
        $products = Product::orderBy('sort_order')
            ->get(['id', 'name', 'price', 'image', 'description', 'category', 'active', 'has_mojado', 'has_seco', 'has_cochinita', 'has_lechon']);

        return response()->json($products);
    }
}
