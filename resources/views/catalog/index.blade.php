@extends('layouts.catalog')

@section('title', 'Food court')

@section('page')
    @if ($search === '')
        <section class="catalog-hero">
            <div class="catalog-hero__copy">
                <h1>Makan apa hari ini?</h1>
                <p>Pilih gerai favoritmu. Cek menu, harga, dan porsi yang tersedia hari ini.</p>
                <a class="button button--light" href="#daftar-tenant">Lihat daftar tenant</a>
            </div>
            <img class="catalog-hero__photo" src="{{ asset('images/catalog/food-court.webp') }}"
                alt="Meja makan dan deretan gerai di food court kampus" width="1200" height="675" fetchpriority="high">
        </section>
    @endif

    <section class="catalog-section" id="daftar-tenant" aria-labelledby="tenant-title">
        <div class="section-heading">
            <div>
                @if ($search === '')
                    <h2 id="tenant-title">Tenant food court</h2>
                @else
                    <h1 id="tenant-title">Hasil pencarian</h1>
                    <p>Menampilkan hasil untuk “{{ $search }}”. <a href="{{ route('home') }}#daftar-tenant">Lihat semua tenant</a></p>
                @endif
            </div>
            <form class="catalog-search" method="get" action="{{ route('home') }}" role="search">
                <label for="q" class="visually-hidden">Cari tenant atau menu</label>
                <input type="search" name="q" id="q" value="{{ $search }}" placeholder="Cari tenant atau menu" maxlength="80">
                <button class="button button--primary" type="submit">Cari</button>
            </form>
        </div>

        <div class="tenant-grid">
            @forelse ($tenants as $tenant)
                <article class="tenant-card">
                    <a href="{{ route('catalog.tenant', ['tenant' => $tenant->slug]) }}" class="tenant-card__link">
                        <x-catalog.photo :path="$tenant->photo_path" :alt="'Gerai '.$tenant->name" class="tenant-card__photo" />
                        <div class="tenant-card__body">
                            <div>
                                @if ($tenant->category)
                                    <p class="item-category">{{ $tenant->category }}</p>
                                @endif
                                <h3>{{ $tenant->name }}</h3>
                            </div>
                            <span class="tenant-card__action">{{ $tenant->menu_items_count }} menu</span>
                        </div>
                    </a>
                </article>
            @empty
                <p class="empty-state">{{ $search === '' ? 'Belum ada tenant di katalog.' : 'Tidak ada tenant atau menu yang cocok.' }}</p>
            @endforelse
        </div>
        <x-catalog.pages :items="$tenants" />
    </section>
@endsection
