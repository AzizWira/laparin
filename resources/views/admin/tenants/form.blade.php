@extends('layouts.catalog')

@section('title', $tenant->exists ? 'Edit tenant' : 'Tambah tenant')

@section('page')
    <a class="back-link" href="{{ route('dashboard.admin') }}">Kembali ke daftar tenant</a>
    <section class="edit-panel">
        <h1>{{ $tenant->exists ? 'Edit tenant' : 'Tambah tenant' }}</h1>
        <form class="catalog-form" method="post" enctype="multipart/form-data"
            action="{{ $tenant->exists ? route('admin.tenants.update', $tenant) : route('admin.tenants.store') }}">
            @csrf
            @if ($tenant->exists)
                @method('PUT')
            @endif
            <x-catalog.field name="name" label="Nama gerai" :value="$tenant->name" maxlength="100" required />
            <x-catalog.field name="category" label="Kategori" :value="$tenant->category" maxlength="50" required />
            <x-catalog.field name="description" label="Deskripsi" type="textarea" :value="$tenant->description" rows="4" maxlength="500" />
            @if ($tenant->photo_path)
                <x-catalog.photo :path="$tenant->photo_path" :alt="'Foto gerai '.$tenant->name" class="edit-panel__preview" />
            @endif
            <x-catalog.field name="photo" label="Foto gerai" type="file" hint="JPG, PNG, atau WebP. Maksimal 2 MB."
                accept="image/jpeg,image/png,image/webp" :required="! $tenant->photo_path" />
            <button class="button button--primary" type="submit">Simpan tenant</button>
        </form>
    </section>
@endsection
