@props(['name', 'label', 'type' => 'text', 'value' => '', 'required' => false])<div class="field">
    <label for="{{ $attributes->get('id', $name) }}">{{ $label }}</label><input
        id="{{ $attributes->get('id', $name) }}" name="{{ $name }}" type="{{ $type }}"
        value="{{ $type === 'password' ? '' : old($name, $value) }}" @required($required) {{ $attributes->except('id') }}>
    @error($name)
        <span class="small" role="alert">{{ $message }}</span>
    @enderror
</div>
