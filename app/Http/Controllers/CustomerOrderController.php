<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerOrderController extends Controller
{
    public function index(Request $request): View
    {
        $orders = $request->user()->orders()->with('tenant')->latest('id')->paginate(12);

        return view('orders.index', ['orders' => $orders, 'tenantView' => false]);
    }

    public function show(Request $request, string $code): View
    {
        $order = $request->user()->orders()->with(['tenant', 'items'])->where('code', $code)->firstOrFail();

        return view('orders.show', ['order' => $order, 'tenantView' => false]);
    }
}
