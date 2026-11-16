@extends('layouts.catalog')

@section('title', $tenant->name)

@section('page')
    <a href="{{ route('home') }}#daftar-tenant" class="back-link">Kembali ke daftar tenant</a>

    <section class="tenant-intro">
        <div class="tenant-intro__copy">
            @if ($tenant->category)
                <p class="item-category">{{ $tenant->category }}</p>
            @endif
            <h1>{{ $tenant->name }}</h1>
            @if ($tenant->description)
                <p>{{ $tenant->description }}</p>
            @endif
        </div>
        <x-catalog.photo :path="$tenant->photo_path" :alt="'Gerai '.$tenant->name" class="tenant-intro__photo" />
    </section>

    <section class="catalog-section" aria-labelledby="menu-title">
        <div class="section-heading">
            <h2 id="menu-title">Menu hari ini</h2>
            @guest
                <a class="button button--outline" href="{{ route('login', ['gerai' => $tenant->slug]) }}">Masuk untuk memesan</a>
            @endguest
        </div>
        <x-orders.feedback />
        <div class="menu-grid">
            @forelse ($menu as $item)
                @php($available = ($item->todayStock?->remaining ?? 0) > 0)
                <article class="menu-card">
                    <x-catalog.photo :path="$item->photo_path" :alt="$item->name" class="menu-card__photo" />
                    <div class="menu-card__body">
                        <div class="menu-card__heading">
                            <h3>{{ $item->name }}</h3>
                            <span @class(['stock-label', 'stock-label--empty' => ! $available])>{{ $available ? 'Tersedia' : 'Habis' }}</span>
                        </div>
                        @if ($item->description)
                            <p>{{ $item->description }}</p>
                        @endif
                        <div class="menu-card__purchase">
                            <strong class="item-price">Rp{{ number_format($item->price, 0, ',', '.') }}</strong>
                            @auth
                                @if (auth()->user()->role === \App\Enums\UserRole::Pelanggan)
                                    <form method="post" action="{{ route('cart.add', $item) }}">
                                        @csrf
                                        <button class="button button--primary" type="submit" @disabled(! $available)
                                            aria-label="Tambah {{ $item->name }} ke keranjang">Tambah</button>
                                    </form>
                                @endif
                            @endauth
                        </div>
                    </div>
                </article>
            @empty
                <p class="empty-state">Tenant ini belum memiliki menu.</p>
            @endforelse
        </div>
        <x-catalog.pages :items="$menu" />
    </section>
@endsection
