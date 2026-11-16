<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\MenuItemStock;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TenantOrderController extends Controller
{
    public function index(Request $request): View
    {
        $tenant = $this->tenant($request);
        $filter = $request->query('status');
        $status = is_string($filter) ? OrderStatus::tryFrom($filter) : null;
        $orders = $tenant->orders()->with('customer')
            ->when($status !== null, fn ($orders) => $orders->where('status', $status->value))
            ->latest('id')->paginate(12)
            ->appends($status === null ? [] : ['status' => $status->value]);

        return view('orders.index', ['orders' => $orders, 'tenantView' => true, 'tenant' => $tenant, 'status' => $status]);
    }

    public function show(Request $request, string $code): View
    {
        $order = $this->tenant($request)->orders()->with(['tenant', 'customer', 'items'])->where('code', $code)->firstOrFail();

        return view('orders.show', ['order' => $order, 'tenantView' => true]);
    }

    public function update(Request $request, string $code): RedirectResponse
    {
        $tenant = $this->tenant($request);
        $data = $request->validate(['status' => ['required', Rule::enum(OrderStatus::class)]]);
        $next = OrderStatus::from($data['status']);

        DB::transaction(function () use ($tenant, $code, $next): void {
            $order = $tenant->orders()->where('code', $code)->lockForUpdate()->firstOrFail();

            if (! in_array($next, $order->status->next(), true)) {
                throw ValidationException::withMessages(['status' => 'Perubahan status ini tidak diizinkan. Periksa status terakhir pesanan.']);
            }

            if ($next === OrderStatus::Dibatalkan) {
                foreach ($order->items()->orderBy('menu_item_id')->get() as $item) {
                    MenuItemStock::query()->where('menu_item_id', $item->menu_item_id)
                        ->where('stock_date', $order->stock_date)->increment('remaining', $item->quantity);
                }
            }

            $order->update(['status' => $next]);
        }, attempts: 3);

        return to_route('tenant.orders.show', ['code' => $code])->with('status', 'Status pesanan diperbarui.');
    }

    private function tenant(Request $request): Tenant
    {
        return $request->user()->tenant ?? abort(403, 'Akun ini belum terhubung ke tenant aktif.');
    }
}
