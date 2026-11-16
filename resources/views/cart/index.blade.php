@extends('layouts.catalog')

@section('title', 'Keranjang')

@section('page')
    <div class="section-heading workspace-heading">
        <div>
            <h1>Keranjang</h1>
            @if ($tenant)
                <p>{{ $tenant->name }}</p>
            @endif
        </div>
        @if ($tenant && ! $tenant->trashed())
            <a class="button button--outline" href="{{ route('catalog.tenant', ['tenant' => $tenant->slug]) }}">Lihat menu gerai</a>
        @endif
    </div>

    <x-orders.feedback />

    @if ($cart === null)
        <div class="order-empty">
            <p>Keranjangmu masih kosong.</p>
            <a class="button button--primary" href="{{ route('home') }}#daftar-tenant">Jelajahi tenant</a>
        </div>
    @else
        <div class="order-grid">
            <div class="order-panel">
                <h2>Menu yang dipilih</h2>
                <ul class="cart-list">
                    @foreach ($lines as $line)
                        <li class="cart-row">
                            <x-catalog.photo :path="$line['item']?->photo_path" alt="" class="cart-row__photo" />
                            <div>
                                <h3>{{ $line['item']?->name ?? 'Menu sudah dihapus' }}</h3>
                                <p>Rp{{ number_format($line['item']?->price ?? 0, 0, ',', '.') }} per porsi</p>
                                <p>Subtotal <strong>Rp{{ number_format($line['lineTotal'], 0, ',', '.') }}</strong></p>
                                @if ($line['remaining'] < $line['quantity'])
                                    <p class="field__error">
                                        {{ $line['remaining'] === 0 ? 'Porsi tidak tersedia.' : 'Sisa '.$line['remaining'].' porsi.' }}
                                    </p>
                                @endif
                            </div>
                            <div class="order-actions cart-row__actions">
                                @if ($line['remaining'] > 0)
                                    <form class="cart-quantity" method="post" action="{{ route('cart.update', $line['id']) }}">
                                        @csrf
                                        @method('PATCH')
                                        <label class="visually-hidden" for="quantity-{{ $line['id'] }}">Jumlah {{ $line['item']->name }}</label>
                                        <input id="quantity-{{ $line['id'] }}" type="number" name="quantity" min="1"
                                            max="{{ min(99, $line['remaining']) }}" value="{{ $line['quantity'] }}" required>
                                        <button class="button button--outline" type="submit">Simpan</button>
                                    </form>
                                @else
                                    <span>{{ $line['quantity'] }} porsi</span>
                                @endif
                                <form method="post" action="{{ route('cart.remove', $line['id']) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="button button--danger" type="submit">Hapus</button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
            <aside class="order-panel order-summary" aria-label="Ringkasan keranjang">
                <h2>Ringkasan</h2>
                <dl class="order-totals">
                    <div><dt>Jumlah porsi</dt><dd>{{ array_sum($cart['quantities']) }}</dd></div>
                    <div><dt>Total</dt><dd>Rp{{ number_format($total, 0, ',', '.') }}</dd></div>
                </dl>
                @unless ($canOrder)
                    <p class="field__error">Sesuaikan porsi atau hapus menu yang habis sebelum checkout.</p>
                @endunless
                <form method="post" action="{{ route('cart.checkout') }}">
                    @csrf
                    <input type="hidden" name="checkout_token" value="{{ $cart['token'] }}">
                    <button class="button button--primary" type="submit" @disabled(! $canOrder)>Checkout</button>
                </form>
                <form method="post" action="{{ route('cart.clear') }}">
                    @csrf
                    @method('DELETE')
                    <button class="button button--outline" type="submit">Kosongkan keranjang</button>
                </form>
            </aside>
        </div>
    @endif
@endsection
