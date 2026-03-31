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
                        <a href="{{ route('presentations.index') }}" class="btn btn-outline-secondary">Back</a>
                        <a href="{{ $document->presentationUrl() }}" class="btn btn-outline-success" target="_blank" rel="noopener noreferrer">Present</a>
                        <a href="{{ route('presentations.edit', $document) }}" class="btn btn-primary">Edit</a>
                    </div>
                </div>

                @if (session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body">
                        <h2 class="h6 text-uppercase text-body-secondary">Description</h2>
                        <p class="mb-3">{{ $document->description ?: 'No description provided.' }}</p>

                        <h2 class="h6 text-uppercase text-body-secondary">Theme</h2>
                        <p class="mb-0">{{ $document->theme?->name ?? 'Default SlideWire Theme' }}</p>
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent d-flex align-items-center justify-content-between">
                        <h2 class="h5 mb-0">Slides</h2>
                        <small class="text-body-secondary">Ordered slide source</small>
                    </div>
                    <div class="card-body">
                        @forelse ($document->slides as $slide)
                            <div class="mb-3">
                                <h3 class="h6 text-body-secondary">Slide {{ $slide->sort_order }}</h3>
                                <pre class="mb-0"><code class="language-markdown">{{ $slide->content }}</code></pre>
                            </div>
                        @empty
                            <p class="text-body-secondary mb-0">No slides found for this presentation.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
