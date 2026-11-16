<?php

namespace App\Http\Controllers;

use App\Models\MenuItem;
use App\Models\MenuItemStock;
use App\Models\Tenant;
use App\Support\CatalogPhoto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TenantMenuController extends Controller
{
    public function index(Request $request): View
    {
        $tenant = $this->tenant($request);
        $menu = $tenant->menuItems()->with('todayStock')->orderBy('name')->paginate(15);

        return view('tenant.menu.index', compact('tenant', 'menu'));
    }

    public function create(Request $request): View
    {
        $this->tenant($request);

        return view('tenant.menu.form', ['item' => new MenuItem]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = $this->tenant($request);
        $this->save($request, $tenant->menuItems()->make());

        return to_route('dashboard.tenant')->with('status', 'Menu berhasil ditambahkan.');
    }

    public function edit(Request $request, string $menu): View
    {
        return view('tenant.menu.form', ['item' => $this->item($request, $menu)]);
    }

    public function update(Request $request, string $menu): RedirectResponse
    {
        $this->save($request, $this->item($request, $menu));

        return to_route('dashboard.tenant')->with('status', 'Menu berhasil diperbarui.');
    }

    public function destroy(Request $request, string $menu): RedirectResponse
    {
        $this->item($request, $menu)->delete();

        return to_route('dashboard.tenant')->with('status', 'Menu sudah diarsipkan.');
    }

    public function updateStock(Request $request, string $menu): RedirectResponse
    {
        $item = $this->item($request, $menu);
        $data = $request->validate(['remaining' => ['required', 'integer', 'min:0', 'max:10000']], [], [
            'remaining' => 'sisa porsi',
        ]);
        $this->setStock($item, $data['remaining']);

        return to_route('dashboard.tenant')->with('status', 'Stok '.$item->name.' sudah diperbarui.');
    }

    private function tenant(Request $request): Tenant
    {
        return $request->user()->tenant ?? abort(403, 'Akun ini belum terhubung ke tenant aktif.');
    }

    private function item(Request $request, string $id): MenuItem
    {
        return $this->tenant($request)->menuItems()->findOrFail($id);
    }

    private function save(Request $request, MenuItem $item): void
    {
        $creating = ! $item->exists;
        $price = $request->input('price');

        if (is_string($price) && preg_match('/\A[0-9]{1,3}(?:\.[0-9]{3}){1,2}\z/', $price) === 1) {
            $request->merge(['price' => str_replace('.', '', $price)]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'price' => ['required', 'integer', 'min:1', 'max:10000000'],
            'initial_stock' => [$creating ? 'required' : 'prohibited', 'integer', 'min:0', 'max:10000'],
            'photo' => [$item->photo_path ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [], [
            'name' => 'nama menu',
            'description' => 'deskripsi',
            'price' => 'harga',
            'initial_stock' => 'stok awal hari ini',
            'photo' => 'foto menu',
        ]);

        $stock = $data['initial_stock'] ?? null;
        unset($data['initial_stock'], $data['photo']);

        if ($creating) {
            $data['slug'] = Str::limit(Str::slug($data['name']) ?: 'menu', 110, '').'-'.Str::lower(Str::random(8));
        }

        CatalogPhoto::save($request->file('photo'), $item->photo_path, function (?string $photoPath) use ($item, $data, $stock): void {
            DB::transaction(function () use ($item, $data, $photoPath, $stock): void {
                $item->fill([...$data, 'photo_path' => $photoPath])->save();

                if ($stock !== null) {
                    $this->setStock($item, $stock);
                }
            });
        });
    }

    private function setStock(MenuItem $item, int $remaining): void
    {
        MenuItemStock::query()->upsert([
            'menu_item_id' => $item->id,
            'stock_date' => MenuItem::stockDate(),
            'remaining' => $remaining,
        ], ['menu_item_id', 'stock_date'], ['remaining']);
    }
}
