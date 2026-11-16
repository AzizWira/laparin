<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('laparin:buat-admin', function (): int {
    $validator = Validator::make([
        'name' => trim((string) $this->ask('Nama admin')),
        'email' => Str::lower(trim((string) $this->ask('Email admin'))),
        'password' => $this->secret('Kata sandi admin'),
        'password_confirmation' => $this->secret('Ulangi kata sandi'),
    ], [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'max:254', 'unique:users,email'],
        'password' => ['required', 'string', 'min:12', 'max:72', 'confirmed'],
    ]);

    if ($validator->fails()) {
        foreach ($validator->errors()->all() as $error) {
            $this->error($error);
        }

        return 1;
    }

    $admin = new User($validator->safe()->only(['name', 'email', 'password']));
    $admin->role = UserRole::Admin;
    $admin->save();

    $this->info('Akun admin berhasil dibuat.');

    return 0;
})->purpose('Membuat akun Admin Laparin');
