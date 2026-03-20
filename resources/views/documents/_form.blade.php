@php
    $title = old('title', $document->title ?? '');
    $description = old('description', $document->description ?? '');
    $content = old('content', $document->content ?? '');
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
        <label for="content" class="form-label mb-0">Markdown</label>
        <small class="text-body-secondary">Toolbar includes preview and formatting shortcuts</small>
    </div>

    <x-markdown-editor id="content" name="content" :value="$content" rows="20" />
</div>

<div class="d-flex flex-wrap gap-2">
    <button type="submit" class="btn btn-primary">
        <i class="bi bi-save me-1"></i>
        {{ $submitLabel }}
    </button>

    <a href="{{ route('documents.index') }}" class="btn btn-outline-secondary">
        Cancel
    </a>
</div>
