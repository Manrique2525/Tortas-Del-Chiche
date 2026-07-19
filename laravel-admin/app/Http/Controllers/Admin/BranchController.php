<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index()
    {
        $branches = Branch::orderBy('sort_order')->get();
        return view('admin.branches.index', compact('branches'));
    }

    public function create()
    {
        return view('admin.branches.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'address'        => 'nullable|string|max:500',
            'phone'          => 'nullable|string|max:50',
            'whatsapp'       => 'nullable|string|max:50',
            'schedule_text'  => 'nullable|string|max:255',
            'latitude'       => 'nullable|numeric|between:-90,90',
            'longitude'      => 'nullable|numeric|between:-180,180',
            'didi_url'       => 'nullable|string|max:500',
            'sort_order'     => 'nullable|integer|min:0',
        ]);

        $selectedDays = $request->input('days', []);
        $validated['schedule'] = [
            'open' => $request->input('schedule_open', '07:00'),
            'close' => $request->input('schedule_close', '14:00'),
            'days' => $selectedDays,
        ];
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        Branch::create($validated);

        return redirect()->route('admin.branches')->with('success', 'Sucursal creada correctamente.');
    }

    public function edit(Branch $branch)
    {
        return view('admin.branches.edit', compact('branch'));
    }

    public function update(Request $request, Branch $branch)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'address'        => 'nullable|string|max:500',
            'phone'          => 'nullable|string|max:50',
            'whatsapp'       => 'nullable|string|max:50',
            'schedule_text'  => 'nullable|string|max:255',
            'latitude'       => 'nullable|numeric|between:-90,90',
            'longitude'      => 'nullable|numeric|between:-180,180',
            'didi_url'       => 'nullable|string|max:500',
            'sort_order'     => 'nullable|integer|min:0',
        ]);

        $selectedDays = $request->input('days', []);
        $validated['schedule'] = [
            'open' => $request->input('schedule_open', '07:00'),
            'close' => $request->input('schedule_close', '14:00'),
            'days' => $selectedDays,
        ];
        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['sort_order'] = $validated['sort_order'] ?? 0;

        $branch->update($validated);

        return redirect()->route('admin.branches')->with('success', 'Sucursal actualizada correctamente.');
    }

    public function destroy(Branch $branch)
    {
        $ordersCount = \App\Models\Order::where('branch', $branch->key)->count();
        if ($ordersCount > 0) {
            return redirect()->route('admin.branches')->with('error', "No se puede eliminar: {$ordersCount} pedido(s) usan esta sucursal.");
        }

        $branch->delete();
        return redirect()->route('admin.branches')->with('success', 'Sucursal eliminada correctamente.');
    }

    public function toggle(Branch $branch)
    {
        $branch->update(['is_active' => !$branch->is_active]);
        return response()->json([
            'success' => true,
            'is_active' => $branch->is_active,
            'message' => $branch->is_active ? 'Sucursal activada' : 'Sucursal desactivada',
        ]);
    }
}
