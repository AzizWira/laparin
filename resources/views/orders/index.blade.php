@extends('layouts.catalog')

@section('title', $tenantView ? 'Pesanan gerai' : 'Pesanan saya')

@section('page')
    <div class="section-heading workspace-heading">
        <div>
            <h1>{{ $tenantView ? 'Pesanan gerai' : 'Pesanan saya' }}</h1>
            @if ($tenantView)
                <p>{{ $tenant->name }}</p>
            @endif
        </div>
        <a class="button button--outline" href="{{ $tenantView ? route('dashboard.tenant') : route('home').'#daftar-tenant' }}">
            {{ $tenantView ? 'Kelola menu' : 'Jelajahi tenant' }}
        </a>
    </div>

    @if ($tenantView)
        <form class="order-filter" method="get" action="{{ route('tenant.orders.index') }}">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">Semua</option>
                @foreach (\App\Enums\OrderStatus::cases() as $option)
                    <option value="{{ $option->value }}" @selected($status === $option)>{{ $option->label() }}</option>
                @endforeach
            </select>
            <button class="button button--outline" type="submit">Tampilkan</button>
        </form>
    @endif

    <ul class="order-list" aria-label="Daftar pesanan">
        @forelse ($orders as $order)
            <li>
                <a class="order-list__link" href="{{ route($tenantView ? 'tenant.orders.show' : 'orders.show', ['code' => $order->code]) }}">
                    <div>
                        <h2>{{ $tenantView ? $order->customer->name : $order->tenant->name }}</h2>
                        <p>#{{ $order->shortCode() }} · {{ $order->created_at->timezone('Asia/Jakarta')->translatedFormat('j M Y, H:i') }} WIB</p>
                    </div>
                    <div class="order-list__amount">
                        <span @class(['stock-label', 'stock-label--empty' => $order->status === \App\Enums\OrderStatus::Dibatalkan])>{{ $order->status->label() }}</span>
                        <strong>Rp{{ number_format($order->total, 0, ',', '.') }}</strong>
                    </div>
                </a>
            </li>
        @empty
            <li class="empty-state">{{ $tenantView ? 'Belum ada pesanan dengan status ini.' : 'Kamu belum punya pesanan.' }}</li>
        @endforelse
    </ul>
    <x-catalog.pages :items="$orders" />
@endsection
