@props([
    'title' => 'Images',
    'images' => collect(),
    'canUpload' => false,
    'uploadRoute' => null,
    'deleteRouteName' => null,
    'ownerId' => null,
    'hint' => 'Use these URLs inside your content.',
    'monacoTargetId' => null,
    'monacoLanguage' => null,
])

<div class="card border-0 shadow-sm h-100">
    <div class="card-header bg-transparent">
        <h3 class="h6 mb-1">{{ $title }}</h3>
        <p class="text-body-secondary mb-0 small">{{ $hint }}</p>
    </div>
    <div class="card-body d-flex flex-column gap-3">
        @if ($canUpload && is_string($uploadRoute) && $uploadRoute !== '')
            <form method="POST" action="{{ $uploadRoute }}" enctype="multipart/form-data" class="d-flex flex-column gap-2">
                @csrf
                <input
                    type="file"
                    name="image"
                    accept="image/*"
                    required
                    class="form-control form-control-sm @error('image') is-invalid @enderror"
                />
                @error('image')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
                <button type="submit" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-upload me-1"></i>
                    Upload image
                </button>
            </form>
        @else
            <div class="alert alert-secondary mb-0 py-2 px-3 small">
                Save this item first, then edit it to upload images.
            </div>
        @endif

        <div class="d-flex flex-column gap-3">
            @forelse ($images as $image)
                @php
                    $publicUrl = asset('storage/' . ltrim($image->path, '/'));
                    $baseName = pathinfo($image->original_name, PATHINFO_FILENAME);
                    $markdownSnippet = '![' . $baseName . '](' . $publicUrl . ')';
                    $cssSnippet = "url('" . $publicUrl . "')";
                    $insertText = $monacoLanguage === 'css' ? $cssSnippet : $markdownSnippet;
                @endphp

                <div class="border rounded p-2 d-flex flex-column gap-2">
                    <img src="{{ $publicUrl }}" alt="{{ $image->original_name }}" class="img-fluid rounded border" loading="lazy" />

                    <div class="small text-body-secondary text-truncate" title="{{ $image->original_name }}">{{ $image->original_name }}</div>

                    <input type="text" readonly class="form-control form-control-sm" value="{{ $publicUrl }}" />

                    <div class="d-flex flex-wrap gap-2">
                        @if ($monacoTargetId !== null)
                            <button
                                type="button"
                                class="btn btn-sm btn-primary"
                                data-monaco-insert
                                data-monaco-insert-target="{{ $monacoTargetId }}"
                                data-monaco-insert-text="{{ $insertText }}"
                            >Insert at cursor</button>
                        @endif
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-copy-text="{{ $publicUrl }}">Copy URL</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-copy-text="{{ $markdownSnippet }}">Copy Markdown</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-copy-text="{{ $cssSnippet }}">Copy CSS</button>
                    </div>

                    @if (is_string($deleteRouteName) && $deleteRouteName !== '' && $ownerId !== null)
                        <form method="POST" action="{{ route($deleteRouteName, [$ownerId, $image->id]) }}" onsubmit="return confirm('Remove this image?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger w-100">Delete</button>
                        </form>
                    @endif
                </div>
            @empty
                <p class="text-body-secondary small mb-0">No images uploaded yet.</p>
            @endforelse
        </div>
    </div>
</div>
