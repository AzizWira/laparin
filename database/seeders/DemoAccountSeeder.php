<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoAccountSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $accounts = DB::transaction(function (): array {
            $tenant = Tenant::query()->firstOrCreate(
                ['slug' => 'kedai-ayam-rasa'],
                ['name' => 'Kedai Ayam Rasa'],
            );

            return [
                $this->createAccount('Admin Laparin', 'admin@laparin.test', UserRole::Admin),
                $this->createAccount('Kedai Ayam Rasa', 'tenant@laparin.test', UserRole::Tenant, $tenant),
                $this->createAccount('Pelanggan Laparin', 'pelanggan@laparin.test', UserRole::Pelanggan),
            ];
        });

        $this->command?->table(['Role', 'Email demo', 'Kata sandi lokal'], $accounts);
    }

    private function createAccount(string $name, string $email, UserRole $role, ?Tenant $tenant = null): array
    {
        $account = User::query()->firstOrNew(['email' => $email]);

        if ($account->exists) {
            return [$account->role->value, $email, 'Akun sudah ada'];
        }

        $password = Str::password(18, symbols: false);
        $account->fill(['name' => $name, 'password' => $password]);
        $account->role = $role;
        $account->tenant()->associate($tenant);
        $account->save();

        return [$role->value, $email, $password];
    }
}
