<?php

use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/products', [ProductController::class, 'index']);
Route::post('/orders', [OrderController::class, 'store']);
Route::get('/orders/pending-count', function () {
    return response()->json([
        'count' => \App\Models\Order::where('status', 'pendiente')->count(),
    ]);
});
Route::get('/coupons', function () {
    $coupons = \App\Models\Coupon::where('active', true)
        ->select('code', 'discount_percent')
        ->get()
        ->mapWithKeys(fn ($c) => [$c->code => ['discount' => $c->discount_percent / 100, 'label' => $c->discount_percent . '%']]);
    return response()->json($coupons);
});
