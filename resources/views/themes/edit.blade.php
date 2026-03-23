<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Theme</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-body-tertiary">
    <main class="container py-5">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <div>
                <h1 class="h2 mb-1">Edit Theme</h1>
                <p class="text-body-secondary mb-0">Update CSS and metadata.</p>
            </div>
            <a href="{{ route('themes.index') }}" class="btn btn-outline-secondary">Back to themes</a>
        </div>

        <div class="row g-4 align-items-start">
            <div class="col-12 col-xl-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5">
                        <form
                            method="POST"
                            action="{{ route('themes.update', $theme) }}"
                            data-theme-form
                            data-theme-update-url="{{ route('themes.update', $theme) }}"
                            data-csrf-token="{{ csrf_token() }}"
                        >
                            @csrf
                            @method('PUT')
                            @include('themes._form', ['submitLabel' => 'Save changes', 'showSaveStatus' => true])
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-4">
                <x-image-library
                    title="Theme Images"
                    hint="Upload theme assets. Click &ldquo;Insert at cursor&rdquo; to place a url() snippet at the CSS editor cursor."
                    :images="$images"
                    :can-upload="$canUploadImages"
                    :upload-route="$uploadImageRoute"
                    :delete-route-name="$deleteImageRouteName"
                    :owner-id="$imageOwnerId"
                    monaco-target-id="css"
                    monaco-language="css"
                />
            </div>
        </div>
    </main>
</body>
</html>
