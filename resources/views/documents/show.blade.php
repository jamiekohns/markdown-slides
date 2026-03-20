<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $document->title }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-body-tertiary">
    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-10">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                    <div>
                        <h1 class="h2 mb-1">{{ $document->title }}</h1>
                        <p class="text-body-secondary mb-0">Updated {{ $document->updated_at->diffForHumans() }}</p>
                    </div>

                    <div class="d-flex gap-2">
                        <a href="{{ route('documents.index') }}" class="btn btn-outline-secondary">Back</a>
                        <a href="{{ $document->presentationUrl() }}" class="btn btn-outline-success" target="_blank" rel="noopener noreferrer">Present</a>
                        <a href="{{ route('documents.edit', $document) }}" class="btn btn-primary">Edit</a>
                    </div>
                </div>

                @if (session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h2 class="h6 text-uppercase text-body-secondary">Description</h2>
                        <p class="mb-0">{{ $document->description ?: 'No description provided.' }}</p>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent d-flex align-items-center justify-content-between">
                        <h2 class="h5 mb-0">Markdown Source</h2>
                        <small class="text-body-secondary">Syntax highlighted</small>
                    </div>
                    <div class="card-body">
                        <pre class="mb-0"><code class="language-markdown">{{ $document->content }}</code></pre>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
