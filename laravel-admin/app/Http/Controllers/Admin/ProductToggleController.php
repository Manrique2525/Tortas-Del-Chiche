<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class ProductToggleController extends Controller
{
    public function toggle(Product $product)
    {
        $newActive = !$product->active;
        $product->update(['active' => $newActive]);

        DB::table('branch_product')
            ->where('product_id', $product->id)
            ->update(['active' => $newActive, 'updated_at' => now()]);

        return response()->json([
            'active' => $product->active,
            'message' => $product->active ? 'Producto activado' : 'Producto desactivado',
        ]);
    }
}
