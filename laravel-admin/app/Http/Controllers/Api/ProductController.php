<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $branchKey = $request->query('branch');

        $products = Product::orderBy('sort_order')
            ->get(['id', 'name', 'price', 'image', 'description', 'category', 'active', 'sort_order', 'has_mojado', 'has_seco', 'has_cochinita', 'has_lechon']);

        if ($branchKey) {
            $branch = Branch::where('key', $branchKey)->first();

            if ($branch) {
                $branchProductMap = $branch->products()
                    ->get(['products.id', 'branch_product.active', 'branch_product.available_options', 'branch_product.price_override'])
                    ->keyBy('id');

                $products = $products->map(function ($product) use ($branchProductMap) {
                    $bp = $branchProductMap->get($product->id);

                    $branchActive = $bp ? (bool) $bp->pivot->active : $product->active;

                    $product->branch_active = $branchActive;

                    $branchOptions = $bp ? $bp->pivot->available_options : null;
                    if ($branchOptions && is_string($branchOptions)) {
                        $branchOptions = json_decode($branchOptions, true);
                    }
                    $product->available_options = $branchOptions;

                    $product->branch_price = $bp ? $bp->pivot->price_override : null;

                    return $product;
                });

                $products = $products->values();
            }
        }

        $products = $products->map(function ($product) {
            $product->image = $product->image ? asset('storage/' . $product->image) : null;
            return $product;
        });

        return response()->json($products);
    }
}
