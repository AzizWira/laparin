@extends('layouts.catalog')

@section('title', $item->exists ? 'Edit menu' : 'Tambah menu')

@push('scripts')
    <script src="{{ asset('js/rupiah-input.js') }}" defer></script>
@endpush

@section('page')
    <a class="back-link" href="{{ route('dashboard.tenant') }}">Kembali ke daftar menu</a>
    <section class="edit-panel">
        <h1>{{ $item->exists ? 'Edit menu' : 'Tambah menu' }}</h1>
        <form class="catalog-form" method="post" enctype="multipart/form-data"
            action="{{ $item->exists ? route('tenant.menu.update', $item) : route('tenant.menu.store') }}">
            @csrf
            @if ($item->exists)
                @method('PUT')
            @endif
            <x-catalog.field name="name" label="Nama menu" :value="$item->name" maxlength="100" required />
            <x-catalog.field name="description" label="Deskripsi" type="textarea" :value="$item->description" rows="4" maxlength="500" />
            <x-catalog.field name="price" label="Harga (rupiah)"
                :value="$item->price === null ? '' : number_format($item->price, 0, ',', '.')"
                inputmode="numeric" pattern="[0-9]+([.][0-9]{3})*" maxlength="10" data-rupiah required />
            @unless ($item->exists)
                <x-catalog.field name="initial_stock" label="Stok awal hari ini" type="number" min="0" max="10000" required />
            @endunless
            @if ($item->photo_path)
                <x-catalog.photo :path="$item->photo_path" :alt="$item->name" class="edit-panel__preview" />
            @endif
            <x-catalog.field name="photo" label="Foto menu" type="file" hint="JPG, PNG, atau WebP. Maksimal 2 MB."
                accept="image/jpeg,image/png,image/webp" :required="! $item->photo_path" />
            <button class="button button--primary" type="submit">Simpan menu</button>
        </form>
    </section>
@endsection
