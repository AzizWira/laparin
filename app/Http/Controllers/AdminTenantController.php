<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Tenant;
use App\Support\CatalogPhoto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminTenantController extends Controller
{
    public function index(): View
    {
        return view('admin.tenants.index', [
            'tenants' => Tenant::query()->withCount('menuItems')->orderBy('name')->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('admin.tenants.form', ['tenant' => new Tenant]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->save($request, new Tenant);

        return to_route('dashboard.admin')->with('status', 'Tenant berhasil ditambahkan.');
    }

    public function edit(Tenant $tenant): View
    {
        return view('admin.tenants.form', compact('tenant'));
    }

    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $this->save($request, $tenant);

        return to_route('dashboard.admin')->with('status', 'Tenant berhasil diperbarui.');
    }

    public function destroy(Tenant $tenant): RedirectResponse
    {
        DB::transaction(function () use ($tenant): void {
            $locked = Tenant::query()->whereKey($tenant->id)->lockForUpdate()->firstOrFail();
            $hasOpenOrders = $locked->orders()->whereNotIn('status', [OrderStatus::Selesai->value, OrderStatus::Dibatalkan->value])->exists();
            if ($hasOpenOrders) {
                throw ValidationException::withMessages(['tenant' => 'Selesaikan pesanan aktif sebelum mengarsipkan tenant ini.']);
            }

            $locked->delete();
        }, attempts: 3);

        return to_route('dashboard.admin')->with('status', 'Tenant sudah diarsipkan.');
    }

    private function save(Request $request, Tenant $tenant): void
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'category' => ['required', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:500'],
            'photo' => [$tenant->photo_path ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [], [
            'name' => 'nama gerai',
            'category' => 'kategori',
            'description' => 'deskripsi',
            'photo' => 'foto gerai',
        ]);
        unset($data['photo']);

        if (! $tenant->exists) {
            $data['slug'] = Str::limit(Str::slug($data['name']) ?: 'gerai', 110, '').'-'.Str::lower(Str::random(8));
        }

        CatalogPhoto::save($request->file('photo'), $tenant->photo_path, function (?string $photoPath) use ($tenant, $data): void {
            $tenant->fill([...$data, 'photo_path' => $photoPath])->save();
        });
    }
}
