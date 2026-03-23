<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>New Presentation</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-body-tertiary">
    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-9">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                    <div>
                        <h1 class="h2 mb-1">Create Presentation</h1>
                        <p class="text-body-secondary mb-0">Add details, then continue in the slide-by-slide editor.</p>
                    </div>
                    <a href="{{ route('documents.index') }}" class="btn btn-outline-secondary">Back to list</a>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5">
                        <form method="POST" action="{{ route('documents.store') }}">
                            @csrf
                            @include('documents._form', ['submitLabel' => 'Create presentation'])
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
