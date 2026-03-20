<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-body-tertiary">
    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4 p-md-5">
                        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                            <div>
                                <h1 class="h2 mb-1">
                                    <i class="bi bi-speedometer2 text-primary me-2"></i>
                                    Dashboard
                                </h1>
                                <p class="text-body-secondary mb-0">
                                    Signed in as <strong>{{ auth()->user()->email }}</strong>
                                </p>
                            </div>

                            <div class="d-flex align-items-center gap-2">
                                <a href="{{ route('documents.index') }}" class="btn btn-outline-primary">
                                    <i class="bi bi-journal-richtext me-1"></i>
                                    Presentations
                                </a>

                                <a href="{{ route('themes.index') }}" class="btn btn-outline-primary">
                                    <i class="bi bi-palette me-1"></i>
                                    Themes
                                </a>

                                <button type="button" class="btn btn-outline-secondary" id="theme-toggle">
                                    <i class="bi bi-moon-stars me-1" id="theme-icon"></i>
                                    <span id="theme-label">Dark mode</span>
                                </button>

                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-danger">
                                        <i class="bi bi-box-arrow-right me-1"></i>
                                        Log out
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

</body>
</html>
