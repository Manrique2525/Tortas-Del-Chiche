<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\ProductToggleController;
use Illuminate\Support\Facades\Route;

// ── Sitio público ──
Route::get('/', function () {
    return response()->file(public_path('index.html'));
});

// ── Admin Auth ──
Route::get('/admin', [AuthController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AuthController::class, 'login'])->name('admin.login.post');
Route::post('/admin/logout', [AuthController::class, 'logout'])->name('admin.logout');

// ── Admin Dashboard (protegido) ──
Route::middleware('admin.auth')->prefix('admin')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

    // Productos CRUD
    Route::get('/products/create', [ProductController::class, 'create'])->name('admin.products.create');
    Route::post('/products', [ProductController::class, 'store'])->name('admin.products.store');
    Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('admin.products.edit');
    Route::put('/products/{product}', [ProductController::class, 'update'])->name('admin.products.update');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('admin.products.destroy');

    // Toggle
    Route::post('/products/{product}/toggle', [ProductToggleController::class, 'toggle'])->name('admin.toggle');

    // Reorder
    Route::post('/products/reorder', [ProductController::class, 'reorder'])->name('admin.products.reorder');

    // Pedidos
    Route::get('/orders', [OrderController::class, 'index'])->name('admin.orders');
    Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus'])->name('admin.orders.status');

    // Sucursales
    Route::get('/branches', [BranchController::class, 'index'])->name('admin.branches');
    Route::get('/branches/create', [BranchController::class, 'create'])->name('admin.branches.create');
    Route::post('/branches', [BranchController::class, 'store'])->name('admin.branches.store');
    Route::get('/branches/{branch}/edit', [BranchController::class, 'edit'])->name('admin.branches.edit');
    Route::put('/branches/{branch}', [BranchController::class, 'update'])->name('admin.branches.update');
    Route::delete('/branches/{branch}', [BranchController::class, 'destroy'])->name('admin.branches.destroy');
    Route::patch('/branches/{branch}/toggle', [BranchController::class, 'toggle'])->name('admin.branches.toggle');

    // Cupones
    Route::get('/coupons', [CouponController::class, 'index'])->name('admin.coupons');
    Route::post('/coupons', [CouponController::class, 'store'])->name('admin.coupons.store');
    Route::patch('/coupons/{coupon}/toggle', [CouponController::class, 'toggle'])->name('admin.coupons.toggle');
    Route::delete('/coupons/{coupon}', [CouponController::class, 'destroy'])->name('admin.coupons.destroy');
});

// ── SPA Fallback ──
Route::get('/admin/{any}', function () {
    return redirect()->route('admin.login');
})->where('any', '.*');
