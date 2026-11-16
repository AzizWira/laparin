@extends('layouts.catalog')

@section('title', 'Pesanan #'.$order->shortCode())

@section('page')
    <a class="back-link" href="{{ route($tenantView ? 'tenant.orders.index' : 'dashboard.pelanggan') }}">Kembali ke daftar pesanan</a>

    <div class="section-heading workspace-heading order-detail-heading">
        <div>
            <h1>Pesanan #{{ $order->shortCode() }}</h1>
            <p>{{ $order->tenant->name }} · {{ $order->created_at->timezone('Asia/Jakarta')->translatedFormat('j F Y, H:i') }} WIB</p>
        </div>
        <span @class(['stock-label', 'stock-label--empty' => $order->status === \App\Enums\OrderStatus::Dibatalkan])>{{ $order->status->label() }}</span>
    </div>

    <x-orders.feedback />

    <div class="order-grid order-grid--detail">
        <div class="order-panel">
            <h2>Rincian pesanan</h2>
            @if ($tenantView)
                <p class="order-muted">Pelanggan {{ $order->customer->name }}</p>
            @endif
            <ul class="receipt-list">
                @foreach ($order->items as $item)
                    <li>
                        <div>
                            <h3>{{ $item->name }}</h3>
                            <p>{{ $item->quantity }} porsi × Rp{{ number_format($item->unit_price, 0, ',', '.') }}</p>
                        </div>
                        <strong>Rp{{ number_format($item->quantity * $item->unit_price, 0, ',', '.') }}</strong>
                    </li>
                @endforeach
            </ul>
            <dl class="order-totals"><div><dt>Total</dt><dd>Rp{{ number_format($order->total, 0, ',', '.') }}</dd></div></dl>
        </div>
        <aside class="order-panel" aria-label="Status pesanan">
            <h2>Status pesanan</h2>
            @if ($order->status === \App\Enums\OrderStatus::Dibatalkan)
                <p class="notice notice--error">Pesanan ini dibatalkan.</p>
            @else
                @php($reached = true)
                <ol class="order-steps">
                    @foreach ([\App\Enums\OrderStatus::Pending, \App\Enums\OrderStatus::Diproses, \App\Enums\OrderStatus::Siap, \App\Enums\OrderStatus::Selesai] as $step)
                        <li @class(['order-steps__reached' => $reached, 'order-steps__current' => $order->status === $step])
                            @if ($order->status === $step) aria-current="step" @endif>{{ $step->label() }}</li>
                        @if ($order->status === $step)
                            @php($reached = false)
                        @endif
                    @endforeach
                </ol>
            @endif
            @if ($tenantView)
                <div class="order-actions">
                    @foreach ($order->status->next() as $next)
                        <form method="post" action="{{ route('tenant.orders.update', ['code' => $order->code]) }}"
                            @if ($next === \App\Enums\OrderStatus::Dibatalkan) onsubmit="return confirm('Batalkan pesanan ini?')" @endif>
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="{{ $next->value }}">
                            <button @class(['button', 'button--danger' => $next === \App\Enums\OrderStatus::Dibatalkan, 'button--primary' => $next !== \App\Enums\OrderStatus::Dibatalkan])
                                type="submit">{{ $next->action() }}</button>
                        </form>
                    @endforeach
                </div>
            @else
                <a class="back-link" href="{{ route('orders.show', ['code' => $order->code]) }}">Muat ulang status</a>
            @endif
        </aside>
    </div>
@endsection
