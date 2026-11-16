@props(['name', 'label', 'type' => 'text', 'value' => '', 'hint' => null])

<div class="field">
    <label for="{{ $name }}">{{ $label }}</label>
    @if ($type === 'textarea')
        <textarea id="{{ $name }}" name="{{ $name }}" {{ $attributes }}
            @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror>{{ old($name, $value) }}</textarea>
    @else
        <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" {{ $attributes }}
            @if ($type !== 'file') value="{{ old($name, $value) }}" @endif
            @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" @elseif ($hint) aria-describedby="{{ $name }}-hint" @enderror>
    @endif
    @if ($hint)
        <p class="field-hint" id="{{ $name }}-hint">{{ $hint }}</p>
    @endif
    @error($name)
        <p class="field__error" id="{{ $name }}-error" role="alert">{{ $message }}</p>
    @enderror
</div>
