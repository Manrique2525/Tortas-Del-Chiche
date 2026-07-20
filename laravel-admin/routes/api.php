<?php

use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\MercadoPagoController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/branches', [BranchController::class, 'index']);
Route::get('/products', [ProductController::class, 'index']);
Route::post('/orders', [OrderController::class, 'store'])->middleware('throttle:20,1');
Route::get('/orders/pending-count', function () {
    return response()->json([
        'count' => \App\Models\Order::where('status', 'pendiente')->count(),
    ]);
});
Route::get('/orders/paid-latest', function () {
    $order = \App\Models\Order::where('status', 'pagado')
        ->where('payment_method', 'mercadopago')
        ->latest()
        ->first(['id', 'customer_name', 'total']);
    return response()->json($order);
});
Route::get('/coupons', function () {
    $coupons = \App\Models\Coupon::where('active', true)
        ->select('code', 'discount_percent')
        ->get()
        ->mapWithKeys(fn ($c) => [$c->code => ['discount' => $c->discount_percent / 100, 'label' => $c->discount_percent . '%']]);
    return response()->json($coupons);
});

Route::post('/mercadopago/create-preference', [MercadoPagoController::class, 'createPreference']);
Route::post('/mercadopago/webhook', [MercadoPagoController::class, 'webhook'])->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
Route::get('/mercadopago/status', [MercadoPagoController::class, 'getPaymentStatus']);
Route::get('/mercadopago/test', [MercadoPagoController::class, 'test']);
