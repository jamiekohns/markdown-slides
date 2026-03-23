@props([
    'id' => 'content',
    'name' => 'content',
    'value' => '',
    'height' => '420px',
])

<textarea
    id="{{ $id }}"
    name="{{ $name }}"
    class="d-none"
>{{ $value }}</textarea>

<div
    class="border rounded overflow-hidden @error($name) border-danger @enderror"
    data-monaco-editor
    data-monaco-target="{{ $id }}"
    data-monaco-language="markdown"
    style="min-height: {{ $height }}; height: {{ $height }};"
></div>

@error($name)
    <div class="invalid-feedback d-block">{{ $message }}</div>
@enderror
