<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;

class ProductToggleController extends Controller
{
    public function toggle(Product $product)
    {
        $product->update(['active' => !$product->active]);

        return response()->json([
            'active' => $product->active,
            'message' => $product->active ? 'Producto activado' : 'Producto desactivado',
        ]);
    }
}
