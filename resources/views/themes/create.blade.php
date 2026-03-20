<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>New Theme</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-body-tertiary">
    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-9">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                    <div>
                        <h1 class="h2 mb-1">Create Theme</h1>
                        <p class="text-body-secondary mb-0">Save reusable CSS for your presentations.</p>
                    </div>
                    <a href="{{ route('themes.index') }}" class="btn btn-outline-secondary">Back to list</a>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5">
                        <form method="POST" action="{{ route('themes.store') }}">
                            @csrf
                            @include('themes._form', ['submitLabel' => 'Create theme'])
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
