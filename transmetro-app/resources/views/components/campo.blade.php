@props(['name', 'label', 'type' => 'text', 'value' => null, 'required' => false])
<div class="field">
    <label for="{{ $name }}">{{ $label }}@if($required) *@endif</label>
    @if ($type === 'select')
        <select id="{{ $name }}" name="{{ $name }}" class="input" @required($required)>{{ $slot }}</select>
    @elseif ($type === 'textarea')
        <textarea id="{{ $name }}" name="{{ $name }}" class="input" rows="4" @required($required)>{{ old($name, $value) }}</textarea>
    @else
        <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" class="input" value="{{ $type === 'password' ? '' : old($name, $value) }}" @required($required) {{ $attributes }}>
    @endif
    @error($name)<div class="error">{{ $message }}</div>@enderror
</div>
