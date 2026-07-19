<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BranchProductController extends Controller
{
    public function index()
    {
        $branches = Branch::orderBy('sort_order')->get();
        $products = Product::orderBy('category')->orderBy('sort_order')->orderBy('name')->get();

        $branchProductMap = [];
        foreach ($branches as $branch) {
            $bps = DB::table('branch_product')
                ->where('branch_id', $branch->id)
                ->get()
                ->keyBy('product_id');
            $branchProductMap[$branch->id] = $bps;
        }

        return view('admin.branch-products.index', compact('branches', 'products', 'branchProductMap'));
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'branch_id' => 'required|exists:sucursales,id',
            'product_id' => 'required|exists:products,id',
            'active' => 'boolean',
            'has_mojado' => 'boolean',
            'has_seco' => 'boolean',
            'has_cochinita' => 'boolean',
            'has_lechon' => 'boolean',
            'price_override' => 'nullable|numeric|min:0',
        ]);

        $options = [];
        if ($request->boolean('has_mojado')) $options['type'][] = 'mojado';
        if ($request->boolean('has_seco')) $options['type'][] = 'seco';

        if ($request->boolean('has_cochinita')) $options['meat'][] = 'cochinita';
        if ($request->boolean('has_lechon')) $options['meat'][] = 'lechon';

        $active = $request->boolean('active', true);

        DB::table('branch_product')->updateOrInsert(
            [
                'branch_id' => $validated['branch_id'],
                'product_id' => $validated['product_id'],
            ],
            [
                'active' => $active,
                'available_options' => !empty($options) ? json_encode($options) : null,
                'price_override' => $validated['price_override'] !== null ? (float) $validated['price_override'] : null,
                'updated_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'active' => $active,
            'message' => $active ? 'Producto activado en sucursal' : 'Producto desactivado en sucursal',
        ]);
    }
}
