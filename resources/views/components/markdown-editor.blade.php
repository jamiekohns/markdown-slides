@props([
    'id' => 'content',
    'name' => 'content',
    'value' => '',
    'rows' => 18,
])

<textarea
    id="{{ $id }}"
    name="{{ $name }}"
    rows="{{ $rows }}"
    class="form-control @error($name) is-invalid @enderror"
    data-markdown-editor
>{{ $value }}</textarea>

@error($name)
    <div class="invalid-feedback d-block">{{ $message }}</div>
@enderror
