@extends('layouts.account')

@section('title', $heading)

@section('content')
    <header class="account-bar">
        <a class="brand" href="{{ route('dashboard') }}">
            <x-brand-mark />
            <span>Laparin</span>
        </a>
        <form action="{{ route('logout') }}" method="post">
            @csrf
            <button class="button button--outline" type="submit">Keluar</button>
        </form>
    </header>

    <main class="dashboard">
        <p class="dashboard__welcome">Halo, {{ auth()->user()->name }}.</p>
        <h1>{{ $heading }}</h1>
        <p>{{ $summary }}</p>
    </main>
@endsection
