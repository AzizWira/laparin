<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AccountAccessTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('roles')]
    public function test_login_routes_each_role_to_its_own_dashboard(UserRole $role): void
    {
        $tenant = $role === UserRole::Tenant
            ? Tenant::create(['name' => 'Gerai Nasi Ayam', 'slug' => 'gerai-nasi-ayam'])
            : null;
        $user = User::factory()->create(['role' => $role, 'tenant_id' => $tenant?->id]);

        $this->get(route('login'))->assertOk()->assertSee('Masuk ke akun');
        $this->post(route('login.store'), ['email' => strtoupper($user->email), 'password' => 'password'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->get(route('dashboard'))->assertRedirect(route('dashboard.'.$role->value));
        $this->get(route('dashboard.'.$role->value))->assertOk();

        foreach (UserRole::cases() as $otherRole) {
            if ($otherRole !== $role) {
                $this->get(route('dashboard.'.$otherRole->value))->assertForbidden();
            }
        }
    }

    public static function roles(): array
    {
        return [
            'admin' => [UserRole::Admin],
            'tenant' => [UserRole::Tenant],
            'pelanggan' => [UserRole::Pelanggan],
        ];
    }

    public function test_tenant_dashboard_only_displays_the_assigned_tenant(): void
    {
        $tenant = Tenant::create(['name' => 'Gerai Nasi Ayam', 'slug' => 'gerai-nasi-ayam']);
        Tenant::create(['name' => 'Gerai Bakso', 'slug' => 'gerai-bakso']);
        $account = User::factory()->create(['role' => UserRole::Tenant, 'tenant_id' => $tenant->id]);

        $this->actingAs($account)->get(route('dashboard.tenant'))
            ->assertOk()->assertSee($tenant->name)->assertDontSee('Gerai Bakso');
    }

    public function test_unassigned_tenant_account_cannot_enter_the_tenant_dashboard(): void
    {
        $account = User::factory()->create(['role' => UserRole::Tenant]);

        $this->actingAs($account)->get(route('dashboard.tenant'))->assertForbidden();
    }

    public function test_login_is_throttled_after_five_failed_attempts(): void
    {
        $account = User::factory()->create();
        $attempt = ['email' => $account->email, 'password' => 'wrong-password'];

        for ($number = 1; $number <= 5; $number++) {
            $this->postJson(route('login.store'), $attempt)
                ->assertUnprocessable()->assertJsonPath('errors.email.0', 'Email atau kata sandi salah.');
        }

        $this->postJson(route('login.store'), [...$attempt, 'password' => 'password'])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->assertGuest();
    }

    public function test_guest_must_sign_in_and_logout_ends_an_existing_session(): void
    {
        $this->get(route('dashboard.admin'))->assertRedirect(route('login'));
        $account = User::factory()->create();
        $this->actingAs($account)->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_cli_can_provision_an_admin_without_a_default_password(): void
    {
        $this->artisan('laparin:buat-admin')
            ->expectsQuestion('Nama admin', 'Admin Laparin')
            ->expectsQuestion('Email admin', 'ADMIN@EXAMPLE.TEST')
            ->expectsQuestion('Kata sandi admin', 'dummy-password-for-test')
            ->expectsQuestion('Ulangi kata sandi', 'dummy-password-for-test')
            ->assertSuccessful();

        $account = User::where('email', 'admin@example.test')->firstOrFail();
        $this->assertSame(UserRole::Admin, $account->role);
        $this->assertNotSame('dummy-password-for-test', $account->password);
    }
}
