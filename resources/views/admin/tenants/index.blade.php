@extends('layouts.catalog')

@section('title', 'Kelola tenant')

@section('page')
    <div class="section-heading workspace-heading">
        <div>
            <p class="item-category">Admin</p>
            <h1>Kelola tenant</h1>
        </div>
        <a class="button button--primary" href="{{ route('admin.tenants.create') }}">Tambah tenant</a>
    </div>

    @if (session('status'))
        <p class="notice" role="status">{{ session('status') }}</p>
    @endif

    <ul class="manage-list" aria-label="Tenant aktif">
        @forelse ($tenants as $tenant)
            <li class="manage-row">
                <x-catalog.photo :path="$tenant->photo_path" :alt="''" class="manage-row__photo" />
                <div class="manage-row__detail">
                    <h2>{{ $tenant->name }}</h2>
                    <p>{{ $tenant->category ?: 'Kategori belum diisi' }} · {{ $tenant->menu_items_count }} menu</p>
                </div>
                <div class="manage-row__actions">
                    <a class="button button--outline" href="{{ route('catalog.tenant', ['tenant' => $tenant->slug]) }}">Lihat</a>
                    <a class="button button--outline" href="{{ route('admin.tenants.edit', $tenant) }}">Edit</a>
                    <form method="post" action="{{ route('admin.tenants.destroy', $tenant) }}" onsubmit="return confirm('Arsipkan tenant ini dari katalog?')">
                        @csrf
                        @method('DELETE')
                        <button class="button button--danger" type="submit">Arsipkan</button>
                    </form>
                </div>
            </li>
        @empty
            <li class="empty-state">Belum ada tenant aktif.</li>
        @endforelse
    </ul>
    <x-catalog.pages :items="$tenants" />
@endsection
