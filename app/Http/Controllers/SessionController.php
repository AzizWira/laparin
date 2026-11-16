<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SessionController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email', 'max:254'],
            'password' => ['required', 'string', 'max:255'],
        ], [
            'email.required' => 'Masukkan email.',
            'email.email' => 'Masukkan alamat email yang valid.',
            'email.max' => 'Alamat email terlalu panjang.',
            'password.required' => 'Masukkan kata sandi.',
            'password.string' => 'Masukkan kata sandi yang valid.',
            'password.max' => 'Kata sandi terlalu panjang.',
        ]);
        $credentials['email'] = Str::lower($credentials['email']);

        $key = 'login:'.hash('sha256', $credentials['email'].'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Terlalu banyak percobaan. Coba lagi dalam '.RateLimiter::availableIn($key).' detik.',
            ]);
        }

        if (! Auth::attempt($credentials)) {
            RateLimiter::hit($key);

            throw ValidationException::withMessages([
                'email' => 'Email atau kata sandi salah.',
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        $returnTenant = $request->input('return_tenant');
        if ($request->user()->role === UserRole::Pelanggan
            && is_string($returnTenant) && strlen($returnTenant) <= 128
            && Tenant::query()->where('slug', $returnTenant)->exists()) {
            return to_route('catalog.tenant', ['tenant' => $returnTenant]);
        }

        return redirect()->route('dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
