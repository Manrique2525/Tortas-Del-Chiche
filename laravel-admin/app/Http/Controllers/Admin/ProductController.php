<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::orderBy('category')->orderBy('sort_order')->orderBy('name')->get();

        return view('admin.dashboard', compact('products'));
    }

    public function create()
    {
        $categories = ['comida', 'bebida'];
        return view('admin.products.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'price'       => 'required|numeric|min:0',
            'description' => 'nullable|string|max:500',
            'category'    => 'required|in:comida,bebida',
            'active'       => 'boolean',
            'has_mojado'   => 'boolean',
            'has_seco'     => 'boolean',
            'has_cochinita' => 'boolean',
            'has_lechon'   => 'boolean',
            'sort_order'   => 'integer|min:0',
            'image'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048|dimensions:w=819,h=546',
        ]);

        $validated['active'] = $request->boolean('active');
        $validated['has_mojado'] = $request->boolean('has_mojado');
        $validated['has_seco'] = $request->boolean('has_seco');
        $validated['has_cochinita'] = $request->boolean('has_cochinita');
        $validated['has_lechon'] = $request->boolean('has_lechon');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        $product = Product::create($validated);

        return redirect()->route('admin.dashboard')
            ->with('success', 'Producto creado correctamente.');
    }

    public function edit(Product $product)
    {
        $categories = ['comida', 'bebida'];
        return view('admin.products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'price'       => 'required|numeric|min:0',
            'description' => 'nullable|string|max:500',
            'category'    => 'required|in:comida,bebida',
            'active'       => 'boolean',
            'has_mojado'   => 'boolean',
            'has_seco'     => 'boolean',
            'has_cochinita' => 'boolean',
            'has_lechon'   => 'boolean',
            'sort_order'   => 'integer|min:0',
            'image'        => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048|dimensions:w=819,h=546',
        ]);

        $validated['active'] = $request->boolean('active');
        $validated['has_mojado'] = $request->boolean('has_mojado');
        $validated['has_seco'] = $request->boolean('has_seco');
        $validated['has_cochinita'] = $request->boolean('has_cochinita');
        $validated['has_lechon'] = $request->boolean('has_lechon');
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        if ($request->hasFile('image')) {
            if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        $product->update($validated);

        return redirect()->route('admin.dashboard')
            ->with('success', 'Producto actualizado correctamente.');
    }

    public function destroy(Product $product)
    {
        if ($product->image && Storage::disk('public')->exists($product->image)) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        return redirect()->route('admin.dashboard')
            ->with('success', 'Producto eliminado correctamente.');
    }

    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:products,id',
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated['ids'] as $index => $id) {
                Product::where('id', $id)->update(['sort_order' => $index]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Orden actualizado',
        ]);
    }
}
