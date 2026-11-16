<?php

use App\Http\Controllers\SessionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

Route::middleware('guest')->group(function (): void {
    Route::view('/login', 'auth.login')->name('login');
    Route::post('/login', [SessionController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [SessionController::class, 'destroy'])->name('logout');

    Route::get('/dashboard', fn (Request $request) => to_route('dashboard.'.$request->user()->role->value))
        ->name('dashboard');

    Route::view('/admin', 'dashboard', [
        'heading' => 'Admin',
        'summary' => 'Kelola tenant dan akun mereka dari sini.',
    ])->middleware('role:admin')->name('dashboard.admin');

    Route::get('/tenant', function (Request $request) {
        $tenant = $request->user()->tenant;

        abort_if($tenant === null, 403, 'Akun ini belum terhubung ke tenant.');

        return view('dashboard', [
            'heading' => $tenant->name,
            'summary' => 'Ruang kerja untuk menu, stok porsi, dan pesanan gerai ini.',
        ]);
    })->middleware('role:tenant')->name('dashboard.tenant');

    Route::view('/pelanggan', 'dashboard', [
        'heading' => 'Pelanggan',
        'summary' => 'Tempat memesan makanan dan memantau pesananmu.',
    ])->middleware('role:pelanggan')->name('dashboard.pelanggan');
});
