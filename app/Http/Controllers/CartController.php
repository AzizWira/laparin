<?php

namespace App\Http\Controllers;

use App\Models\MenuItem;
use App\Models\Tenant;
use App\Support\PlaceOrder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CartController extends Controller
{
    private const SESSION_KEY = 'laparin.cart';

    public function index(Request $request): View
    {
        $cart = $request->session()->get(self::SESSION_KEY);
        $lines = [];
        $tenant = null;
        $total = 0;
        $canOrder = false;

        if ($cart !== null) {
            $tenant = Tenant::withTrashed()->find($cart['tenant_id']);
            $canOrder = $tenant !== null && ! $tenant->trashed();
            $items = MenuItem::withTrashed()->with('todayStock')->where('tenant_id', $cart['tenant_id'])
                ->whereKey(array_keys($cart['quantities']))->get()->keyBy('id');

            foreach ($cart['quantities'] as $id => $quantity) {
                $item = $items->get($id);
                $remaining = $tenant?->trashed() || $item?->trashed() ? 0 : ($item?->todayStock?->remaining ?? 0);
                $lineTotal = ($item?->price ?? 0) * $quantity;
                $lines[] = compact('id', 'item', 'quantity', 'remaining', 'lineTotal');
                $canOrder = $canOrder && $remaining >= $quantity;
                $total += $lineTotal;
            }

            $quote = $canOrder ? $total : null;
            if ($cart['quote'] !== null && $cart['quote'] !== $quote) {
                $cart['token'] = (string) Str::uuid();
            }
            $cart['quote'] = $quote;
            $request->session()->put(self::SESSION_KEY, $cart);
        }

        return view('cart.index', compact('cart', 'lines', 'tenant', 'total', 'canOrder'));
    }

    public function add(Request $request, MenuItem $menu): RedirectResponse
    {
        $tenant = $menu->tenant ?? abort(404);
        $cart = $request->session()->get(self::SESSION_KEY);

        if ($cart !== null && $cart['tenant_id'] !== $tenant->id) {
            return to_route('cart.index')->withErrors([
                'cart' => 'Keranjang berisi menu dari gerai lain. Selesaikan pesanan atau kosongkan keranjang dulu.',
            ]);
        }

        $quantities = $cart['quantities'] ?? [];
        $quantity = ($quantities[$menu->id] ?? 0) + 1;
        $this->ensureAvailable($menu, $quantity);
        $quantities[$menu->id] = $quantity;
        $this->save($request, $tenant->id, $quantities);

        return to_route('catalog.tenant', ['tenant' => $tenant->slug])
            ->with('status', $menu->name.' ditambahkan ke keranjang.');
    }

    public function update(Request $request, int $menu): RedirectResponse
    {
        $cart = $request->session()->get(self::SESSION_KEY);
        abort_unless(isset($cart['quantities'][$menu]), 404);
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:1', 'max:99']], [
            'quantity.*' => 'Masukkan jumlah antara 1 dan 99 porsi.',
        ]);
        $item = MenuItem::query()->where('tenant_id', $cart['tenant_id'])->findOrFail($menu);
        abort_if($item->tenant === null, 404);
        $this->ensureAvailable($item, $data['quantity']);
        $cart['quantities'][$menu] = (int) $data['quantity'];
        $this->save($request, $cart['tenant_id'], $cart['quantities']);

        return to_route('cart.index')->with('status', 'Jumlah porsi diperbarui.');
    }

    public function remove(Request $request, int $menu): RedirectResponse
    {
        $cart = $request->session()->get(self::SESSION_KEY);
        abort_unless(isset($cart['quantities'][$menu]), 404);
        unset($cart['quantities'][$menu]);
        $this->save($request, $cart['tenant_id'], $cart['quantities']);

        return to_route('cart.index')->with('status', 'Menu dihapus dari keranjang.');
    }

    public function clear(Request $request): RedirectResponse
    {
        $request->session()->forget(self::SESSION_KEY);

        return to_route('cart.index')->with('status', 'Keranjang dikosongkan.');
    }

    public function checkout(Request $request, PlaceOrder $checkout): RedirectResponse
    {
        $token = $request->validate(['checkout_token' => ['required', 'uuid']])['checkout_token'];
        $cart = $request->session()->get(self::SESSION_KEY);
        $customer = $request->user();

        if ($cart === null || ! hash_equals($cart['token'], $token) || $cart['quote'] === null) {
            $previous = $customer->orders()->where('checkout_token', $token)->first();

            return $previous === null
                ? to_route('cart.index')->withErrors(['cart' => 'Tinjau kembali keranjang sebelum membuat pesanan.'])
                : to_route('orders.show', ['code' => $previous->code]);
        }

        $order = $checkout->handle($customer, $cart['tenant_id'], $cart['quantities'], $token, $cart['quote']);
        $request->session()->forget(self::SESSION_KEY);

        return to_route('orders.show', ['code' => $order->code])->with('status', 'Pesanan berhasil dibuat.');
    }

    private function ensureAvailable(MenuItem $menu, int $quantity): void
    {
        if ($quantity > 99 || ($menu->todayStock?->remaining ?? 0) < $quantity) {
            throw ValidationException::withMessages([
                'cart' => 'Jumlah '.$menu->name.' melebihi stok hari ini atau batas 99 porsi.',
            ]);
        }
    }

    /** @param array<int, int> $quantities */
    private function save(Request $request, int $tenantId, array $quantities): void
    {
        if ($quantities === []) {
            $request->session()->forget(self::SESSION_KEY);

            return;
        }

        $request->session()->put(self::SESSION_KEY, [
            'tenant_id' => $tenantId,
            'quantities' => $quantities,
            'token' => (string) Str::uuid(),
            'quote' => null,
        ]);
    }
}
