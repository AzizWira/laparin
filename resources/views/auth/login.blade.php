@extends('layouts.account')

@section('title', 'Masuk')

@section('content')
    <main class="sign-in">
        <div class="sign-in__intro">
            <div class="brand">
                <x-brand-mark />
                <span>Laparin</span>
            </div>
            <p class="sign-in__display">Pesan makanan<br> di food court.</p>
        </div>

        <section class="sign-in__panel" aria-labelledby="sign-in-title">
            <h1 id="sign-in-title">Masuk ke akun</h1>

            <form action="{{ route('login.store') }}" method="post" class="account-form">
                @csrf
                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}"
                        autocomplete="username" required
                        @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                    @error('email')
                        <p class="field__error" id="email-error" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <div class="field">
                    <label for="password">Kata sandi</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required
                        @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                    @error('password')
                        <p class="field__error" id="password-error" role="alert">{{ $message }}</p>
                    @enderror
                </div>

                <button class="button button--primary" type="submit">Masuk</button>
            </form>
        </section>
    </main>
@endsection
