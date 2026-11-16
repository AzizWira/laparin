@extends('layouts.account')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/catalog.css') }}">
@endpush

@section('content')
    <a class="skip-link" href="#konten">Langsung ke konten</a>
    <header class="site-bar">
        <nav class="site-bar__inner shell" aria-label="Navigasi utama">
            <a class="brand" href="{{ route('home') }}">
                <x-brand-mark />
                <span>Laparin</span>
            </a>
            <div class="site-bar__actions">
                @auth
                    <a class="site-bar__link" href="{{ route('dashboard') }}">Dasbor</a>
                    <form action="{{ route('logout') }}" method="post">
                        @csrf
                        <button class="button button--outline" type="submit">Keluar</button>
                    </form>
                @else
                    <a class="button button--outline" href="{{ route('login') }}">Masuk</a>
                @endauth
            </div>
        </nav>
    </header>
    <main class="shell site-content" id="konten">
        @yield('page')
    </main>
@endsection
