@php
    $title = old('title', $document->title ?? '');
    $slug = old('slug', $document->slug ?? '');
    $description = old('description', $document->description ?? '');
    $themeId = old('theme_id', $document->theme_id ?? '');
@endphp

<div class="mb-3">
    <label for="title" class="form-label">Title</label>
    <input
        id="title"
        name="title"
        type="text"
        required
        maxlength="255"
        value="{{ $title }}"
        class="form-control @error('title') is-invalid @enderror"
        placeholder="Document title"
    />
    @error('title')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="slug" class="form-label">Slug</label>
    <input
        id="slug"
        name="slug"
        type="text"
        maxlength="255"
        value="{{ $slug }}"
        class="form-control @error('slug') is-invalid @enderror"
        placeholder="my-presentation"
    />
    <div class="form-text">Used for the public presentation URL (for example, /my-presentation).</div>
    @error('slug')
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
    <label for="theme_id" class="form-label">Theme</label>
    <select
        id="theme_id"
        name="theme_id"
        class="form-select @error('theme_id') is-invalid @enderror"
    >
        <option value="">Default SlideWire Theme</option>
        @foreach ($themes as $theme)
            <option value="{{ $theme->id }}" @selected((string) $themeId === (string) $theme->id)>{{ $theme->name }}</option>
        @endforeach
    </select>
    <div class="form-text">Choose optional custom CSS to load after SlideWire's core styles.</div>
    @error('theme_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="d-flex flex-wrap gap-2">
    <button type="submit" class="btn btn-primary" data-document-metadata-save>
        <i class="bi bi-save me-1"></i>
        {{ $submitLabel }}
    </button>

    <a href="{{ route('presentations.index') }}" class="btn btn-outline-secondary">
        Cancel
    </a>
</div>
