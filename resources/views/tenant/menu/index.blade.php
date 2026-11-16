@extends('layouts.catalog')

@section('title', 'Kelola menu')

@section('page')
    <div class="section-heading workspace-heading">
        <div>
            <p class="item-category">Tenant · {{ $tenant->name }}</p>
            <h1>Kelola menu</h1>
            <p>Stok porsi untuk {{ now('Asia/Jakarta')->translatedFormat('j F Y') }}.</p>
        </div>
        <a class="button button--primary" href="{{ route('tenant.menu.create') }}">Tambah menu</a>
    </div>

    @if (session('status'))
        <p class="notice" role="status">{{ session('status') }}</p>
    @endif
    @error('remaining')
        <p class="field__error notice notice--error" role="alert">{{ $message }}</p>
    @enderror

    <ul class="manage-list" aria-label="Menu aktif">
        @forelse ($menu as $item)
            <li class="manage-row manage-row--menu">
                <x-catalog.photo :path="$item->photo_path" :alt="''" class="manage-row__photo" />
                <div class="manage-row__detail">
                    <h2>{{ $item->name }}</h2>
                    <p>Rp{{ number_format($item->price, 0, ',', '.') }}</p>
                </div>
                <div class="menu-controls">
                    <form class="stock-form" method="post" action="{{ route('tenant.menu.stock', $item) }}">
                        @csrf
                        @method('PATCH')
                        <label for="stock-{{ $item->id }}">Sisa porsi</label>
                        <input id="stock-{{ $item->id }}" name="remaining" type="number" min="0" max="10000"
                            value="{{ $item->todayStock?->remaining ?? 0 }}" required aria-label="Sisa porsi {{ $item->name }}">
                        <button class="button button--outline" type="submit">Simpan stok</button>
                    </form>
                    <div class="manage-row__actions">
                        <a class="button button--outline" href="{{ route('tenant.menu.edit', $item) }}">Edit</a>
                        <form method="post" action="{{ route('tenant.menu.destroy', $item) }}" onsubmit="return confirm('Arsipkan menu ini dari katalog?')">
                            @csrf
                            @method('DELETE')
                            <button class="button button--danger" type="submit">Arsipkan</button>
                        </form>
                    </div>
                </div>
            </li>
        @empty
            <li class="empty-state">Gerai ini belum memiliki menu.</li>
        @endforelse
    </ul>
    <x-catalog.pages :items="$menu" />
@endsection
