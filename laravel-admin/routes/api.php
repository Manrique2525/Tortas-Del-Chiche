<?php

use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\StripeController;
use Illuminate\Support\Facades\Route;

Route::get('/branches', [BranchController::class, 'index']);
Route::get('/products', [ProductController::class, 'index']);
Route::post('/orders', [OrderController::class, 'store'])->middleware('throttle:10,1');
Route::get('/orders/pending-count', function () {
    return response()->json([
        'count' => \App\Models\Order::where('status', 'pendiente')->count(),
    ]);
})->middleware('throttle:30,1');
Route::get('/orders/paid-latest', function () {
    $order = \App\Models\Order::where('status', 'pagado')
        ->where('payment_method', 'stripe')
        ->latest()
        ->first(['id', 'customer_name', 'total']);
    return response()->json($order);
})->middleware('throttle:30,1');
Route::get('/coupons', function () {
    $coupons = \App\Models\Coupon::where('active', true)
        ->select('code', 'discount_percent')
        ->get()
        ->mapWithKeys(fn ($c) => [$c->code => ['discount' => $c->discount_percent / 100, 'label' => $c->discount_percent . '%']]);
    return response()->json($coupons);
})->middleware('throttle:30,1');

Route::post('/stripe/create-checkout-session', [StripeController::class, 'createCheckoutSession'])->middleware('throttle:5,1');
Route::post('/stripe/cancel-order', [StripeController::class, 'cancelOrder'])->middleware('throttle:10,1');
Route::post('/stripe/webhook', [StripeController::class, 'webhook'])->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
Route::get('/stripe/status', [StripeController::class, 'getPaymentStatus'])->middleware('throttle:15,1');
