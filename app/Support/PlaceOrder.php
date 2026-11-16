<?php

namespace App\Support;

use App\Enums\OrderStatus;
use App\Models\MenuItem;
use App\Models\MenuItemStock;
use App\Models\Order;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class PlaceOrder
{
    /** @param array<int, int> $quantities */
    public function handle(User $customer, int $tenantId, array $quantities, string $token, int $quotedTotal): Order
    {
        return DB::transaction(function () use ($customer, $tenantId, $quantities, $token, $quotedTotal): Order {
            // The customer row serializes repeated requests that use the same checkout token.
            User::query()->whereKey($customer->id)->lockForUpdate()->firstOrFail();
            $previous = $customer->orders()->where('checkout_token', $token)->first();
            if ($previous !== null) {
                return $previous;
            }

            $tenant = Tenant::query()->whereKey($tenantId)->lockForUpdate()->first();
            if ($tenant === null || $quantities === []) {
                throw ValidationException::withMessages(['cart' => 'Keranjang kosong atau gerai sudah tidak tersedia.']);
            }

            $date = MenuItem::stockDate();
            $items = $tenant->menuItems()->whereKey(array_keys($quantities))->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $stocks = MenuItemStock::query()->where('stock_date', $date)
                ->whereIn('menu_item_id', array_keys($quantities))->orderBy('menu_item_id')
                ->lockForUpdate()->get()->keyBy('menu_item_id');

            $lines = [];
            $total = 0;
            foreach ($quantities as $menuId => $quantity) {
                $item = $items->get($menuId);
                $stock = $stocks->get($menuId);
                if ($item === null || $stock === null || $stock->remaining < $quantity) {
                    $message = $item === null
                        ? 'Ada menu yang sudah tidak tersedia. Periksa kembali keranjang.'
                        : 'Porsi '.$item->name.' tidak mencukupi. Sesuaikan jumlah di keranjang.';
                    throw ValidationException::withMessages(['cart' => $message]);
                }

                $lines[] = [
                    'menu_item_id' => $item->id,
                    'name' => $item->name,
                    'unit_price' => $item->price,
                    'quantity' => $quantity,
                ];
                $total += $item->price * $quantity;
            }

            if ($total !== $quotedTotal) {
                throw ValidationException::withMessages(['cart' => 'Harga menu berubah. Periksa total terbaru sebelum memesan.']);
            }

            $order = $customer->orders()->create([
                'tenant_id' => $tenant->id,
                'code' => (string) Str::ulid(),
                'checkout_token' => $token,
                'stock_date' => $date,
                'status' => OrderStatus::Pending,
                'total' => $total,
            ]);
            $order->items()->createMany($lines);

            foreach ($stocks as $menuId => $stock) {
                $stock->decrement('remaining', $quantities[$menuId]);
            }

            return $order;
        }, attempts: 3);
    }
}
