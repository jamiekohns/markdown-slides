<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Presentation</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-body-tertiary">
    <main class="container py-5">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div>
                <h1 class="h2 mb-1">Edit Presentation</h1>
                <p class="text-body-secondary mb-0">Edit document details and manage ordered slide content in one place.</p>
            </div>
            <a href="{{ route('presentations.index') }}" class="btn btn-outline-secondary">Back to presentations</a>
        </div>

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        <div class="row g-4 align-items-start">
            <div class="col-12 col-xl-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5">
                        <h2 class="h5 mb-3">Presentation Details</h2>
                        <form method="POST" action="{{ route('presentations.update', $document) }}">
                            @csrf
                            @method('PUT')
                            @include('documents._form', ['submitLabel' => 'Save details'])
                        </form>
                    </div>
                </div>

                <div
                    class="card border-0 shadow-sm mt-4"
                    data-slide-editor
                    data-document-id="{{ $document->id }}"
                    data-slides-index-url="{{ route('presentations.slides.index', $document, false) }}"
                    data-slides-store-url="{{ route('presentations.slides.store', $document, false) }}"
                    data-slides-update-url-template="{{ route('presentations.slides.update', ['document' => $document->id, 'slide' => '__SLIDE_ID__'], false) }}"
                    data-slides-delete-url-template="{{ route('presentations.slides.destroy', ['document' => $document->id, 'slide' => '__SLIDE_ID__'], false) }}"
                    data-slides-reorder-url="{{ route('presentations.slides.reorder', $document, false) }}"
                    data-slides-save-all-url="{{ route('presentations.slides.save-all', $document, false) }}"
                    data-slides-export-url="{{ route('presentations.slides.export', $document, false) }}"
                    data-slides-import-url="{{ route('presentations.slides.import', $document, false) }}"
                    data-csrf-token="{{ csrf_token() }}"
                >
                    <div class="card-header bg-transparent d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div>
                            <h2 class="h5 mb-0">Slides</h2>
                            <small class="text-body-secondary">Drag to reorder. Changes autosave while you edit.</small>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('presentations.slides.export', $document) }}" class="btn btn-sm btn-outline-success">Export markdown</a>
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-slide-import-trigger>Import markdown</button>
                            <input type="file" class="d-none" data-slide-import-input accept=".md,.markdown,.txt,text/markdown,text/plain">
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-slide-save-all>Save all now</button>
                            <button type="button" class="btn btn-sm btn-primary" data-slide-add>Add slide</button>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12 col-lg-4">
                                <ul class="list-group" data-slide-list></ul>
                            </div>

                            <div class="col-12 col-lg-8">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <strong data-slide-active-label>Slide</strong>
                                    <small class="text-body-secondary" data-slide-save-status>Loading slides...</small>
                                </div>

                                <div class="mb-3">
                                    <div id="active-slide-editor" data-monaco-editor data-monaco-target="active-slide-textarea" data-monaco-language="markdown" style="height: 520px;"></div>
                                    <textarea id="active-slide-textarea" class="d-none" aria-hidden="true"></textarea>
                                </div>

                                <div class="d-flex flex-wrap gap-2">
                                    <button type="button" class="btn btn-outline-danger" data-slide-delete>Delete slide</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-4">
                <x-image-library
                    title="Presentation Images"
                    hint="Upload deck-specific assets. Click &ldquo;Insert at cursor&rdquo; to place a Markdown image snippet at the editor cursor."
                    :images="$images"
                    :can-upload="$canUploadImages"
                    :upload-route="$uploadImageRoute"
                    :delete-route-name="$deleteImageRouteName"
                    :owner-id="$imageOwnerId"
                    monaco-target-id="active-slide-editor"
                    monaco-language="markdown"
                />
            </div>
        </div>
    </main>
</body>
</html>
