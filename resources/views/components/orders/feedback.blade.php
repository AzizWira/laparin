@if (session('status'))
    <p class="notice" role="status">{{ session('status') }}</p>
@endif
@if ($errors->any())
    <div class="notice notice--error" role="alert">
        @foreach ($errors->all() as $message)
            <p>{{ $message }}</p>
        @endforeach
    </div>
@endif
