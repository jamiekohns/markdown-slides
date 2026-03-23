@php
    $name = old('name', $theme->name ?? '');
    $description = old('description', $theme->description ?? '');
    $css = old('css', $theme->css ?? '');
@endphp

<div class="mb-3">
    <label for="name" class="form-label">Name</label>
    <input
        id="name"
        name="name"
        type="text"
        required
        maxlength="255"
        value="{{ $name }}"
        class="form-control @error('name') is-invalid @enderror"
        placeholder="Theme name"
    />
    @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="description" class="form-label">Description</label>
    <textarea
        id="description"
        name="description"
        rows="3"
        maxlength="1000"
        class="form-control @error('description') is-invalid @enderror"
        placeholder="Optional short summary"
    >{{ $description }}</textarea>
    @error('description')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <div class="d-flex align-items-center justify-content-between mb-2">
        <label for="css" class="form-label mb-0">Theme CSS</label>
        <small class="text-body-secondary">CSS syntax highlighting enabled</small>
    </div>

    <textarea
        id="css"
        name="css"
        required
        class="visually-hidden"
    >{{ $css }}</textarea>

    <div
        class="border rounded overflow-hidden @error('css') border-danger @enderror"
        data-monaco-editor
        data-monaco-target="css"
        data-monaco-language="css"
        style="min-height: 520px; height: 520px;"
    ></div>

    @error('css')
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>

<div class="d-flex flex-wrap align-items-center gap-2">
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-save me-1"></i>
        {{ $submitLabel }}
    </button>

    <a href="{{ route('themes.index') }}" class="btn btn-outline-secondary">
        Cancel
    </a>

    @if ($showSaveStatus ?? false)
        <small class="text-body-secondary ms-1" data-theme-save-status></small>
    @endif
</div>
