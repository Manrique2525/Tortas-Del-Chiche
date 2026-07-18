<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function index()
    {
        $coupons = Coupon::latest()->get();
        return view('admin.coupons.index', compact('coupons'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code'             => 'required|string|max:50|unique:coupons,code',
            'discount_percent' => 'required|numeric|min:1|max:100',
        ]);

        Coupon::create([
            'code'             => strtoupper(trim($validated['code'])),
            'discount_percent' => $validated['discount_percent'],
            'active'           => true,
        ]);

        return redirect()->route('admin.coupons')->with('success', 'Cupón creado correctamente.');
    }

    public function toggle(Coupon $coupon)
    {
        $coupon->update(['active' => !$coupon->active]);
        return response()->json([
            'success' => true,
            'active'  => $coupon->active,
            'message' => $coupon->active ? 'Cupón activado' : 'Cupón desactivado',
        ]);
    }

    public function destroy(Coupon $coupon)
    {
        $coupon->delete();
        return redirect()->route('admin.coupons')->with('success', 'Cupón eliminado correctamente.');
    }
}
