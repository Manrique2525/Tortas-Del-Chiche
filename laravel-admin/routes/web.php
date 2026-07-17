<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductToggleController;
use Illuminate\Support\Facades\Route;

// ── Admin Auth ──
Route::get('/admin', [AuthController::class, 'showLogin'])->name('admin.login');
Route::post('/admin/login', [AuthController::class, 'login'])->name('admin.login.post');
Route::post('/admin/logout', [AuthController::class, 'logout'])->name('admin.logout');

// ── Admin Dashboard (protegido) ──
Route::middleware('admin.auth')->prefix('admin')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::post('/products/{product}/toggle', [ProductToggleController::class, 'toggle'])->name('admin.toggle');
});

// ── SPA Fallback ──
Route::get('/admin/{any}', function () {
    return redirect()->route('admin.login');
})->where('any', '.*');
