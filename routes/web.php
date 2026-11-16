<?php

use App\Http\Controllers\AdminTenantController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CustomerOrderController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\TenantMenuController;
use App\Http\Controllers\TenantOrderController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::pattern('menu', '[0-9]+');
Route::pattern('code', '[0-9A-HJKMNP-TV-Z]{26}');

Route::get('/', [CatalogController::class, 'index'])->name('home');
Route::get('/gerai/{tenant:slug}', [CatalogController::class, 'show'])->name('catalog.tenant');
Route::get('/foto-katalog/{filename}', [CatalogController::class, 'photo'])
    ->where('filename', '[a-zA-Z0-9]+\.(jpg|jpeg|png|webp)')
    ->name('catalog.photo');

Route::middleware('guest')->group(function (): void {
    Route::view('/login', 'auth.login')->name('login');
    Route::post('/login', [SessionController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [SessionController::class, 'destroy'])->name('logout');

    Route::get('/dashboard', fn (Request $request) => to_route('dashboard.'.$request->user()->role->value))
        ->name('dashboard');

    Route::prefix('admin')->middleware('role:admin')->group(function (): void {
        Route::get('/', [AdminTenantController::class, 'index'])->name('dashboard.admin');
        Route::resource('tenant', AdminTenantController::class)->except(['index', 'show'])->names('admin.tenants');
    });

    Route::prefix('tenant')->middleware('role:tenant')->group(function (): void {
        Route::get('/', [TenantMenuController::class, 'index'])->name('dashboard.tenant');
        Route::resource('menu', TenantMenuController::class)->except(['index', 'show'])->names('tenant.menu');
        Route::patch('/menu/{menu}/stok', [TenantMenuController::class, 'updateStock'])->name('tenant.menu.stock');
        Route::get('/pesanan', [TenantOrderController::class, 'index'])->name('tenant.orders.index');
        Route::get('/pesanan/{code}', [TenantOrderController::class, 'show'])->name('tenant.orders.show');
        Route::patch('/pesanan/{code}/status', [TenantOrderController::class, 'update'])->name('tenant.orders.update');
    });

    Route::middleware('role:pelanggan')->group(function (): void {
        Route::get('/pelanggan', [CustomerOrderController::class, 'index'])->name('dashboard.pelanggan');
        Route::get('/pesanan/{code}', [CustomerOrderController::class, 'show'])->name('orders.show');

        Route::prefix('keranjang')->name('cart.')->group(function (): void {
            Route::get('/', [CartController::class, 'index'])->block()->name('index');
            Route::post('/menu/{menu}', [CartController::class, 'add'])->block()->name('add');
            Route::patch('/menu/{menu}', [CartController::class, 'update'])->block()->name('update');
            Route::delete('/menu/{menu}', [CartController::class, 'remove'])->block()->name('remove');
            Route::delete('/', [CartController::class, 'clear'])->block()->name('clear');
            Route::post('/checkout', [CartController::class, 'checkout'])->block()->name('checkout');
        });
    });
});
