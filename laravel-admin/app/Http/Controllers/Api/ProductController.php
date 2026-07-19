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
            ->get(['id', 'name', 'price', 'image', 'description', 'category', 'active', 'has_type', 'has_meat']);

        return response()->json($products);
    }
}
