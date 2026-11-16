@props(['path', 'alt', 'class' => ''])

@if ($path)
    <img class="{{ $class }}" src="{{ \App\Support\CatalogPhoto::url($path) }}"
        alt="{{ $alt }}" loading="lazy" decoding="async">
@else
    <div class="{{ $class }} photo-empty" role="img" aria-label="Foto belum tersedia">Foto belum tersedia</div>
@endif
