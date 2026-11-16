@props(['items'])

@if ($items->hasPages())
    <nav class="page-nav" aria-label="Halaman hasil">
        @if ($items->previousPageUrl())
            <a class="button button--outline" href="{{ $items->previousPageUrl() }}">Sebelumnya</a>
        @endif
        <span>Halaman {{ $items->currentPage() }} dari {{ $items->lastPage() }}</span>
        @if ($items->nextPageUrl())
            <a class="button button--outline" href="{{ $items->nextPageUrl() }}">Berikutnya</a>
        @endif
    </nav>
@endif
