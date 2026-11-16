<?php

use App\Http\Controllers\AdminTenantController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\TenantMenuController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::pattern('menu', '[0-9]+');

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
    });

    Route::view('/pelanggan', 'dashboard', [
        'heading' => 'Pelanggan',
        'summary' => 'Tempat memesan makanan dan memantau pesananmu.',
    ])->middleware('role:pelanggan')->name('dashboard.pelanggan');
});
