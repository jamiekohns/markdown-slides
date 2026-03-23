<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Themes</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-body-tertiary">
    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-10">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                    <div>
                        <h1 class="h2 mb-1">
                            <i class="bi bi-palette text-primary me-2"></i>
                            My Themes
                        </h1>
                        <p class="text-body-secondary mb-0">Manage reusable CSS themes for your presentations.</p>
                    </div>

                    <div class="d-flex gap-2">
                        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-speedometer2 me-1"></i>
                            Dashboard
                        </a>
                        <a href="{{ route('documents.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-journal-richtext me-1"></i>
                            Presentations
                        </a>
                        <a href="{{ route('themes.create') }}" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-1"></i>
                            New Theme
                        </a>
                    </div>
                </div>

                @if (session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-transparent">
                        <h2 class="h5 mb-0">Active</h2>
                    </div>
                    <div class="card-body p-0">
                        @if ($themes->isEmpty())
                            <p class="text-body-secondary m-0 p-4">No themes yet. Create your first CSS theme.</p>
                        @else
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Description</th>
                                            <th>Updated</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($themes as $theme)
                                            <tr>
                                                <td>
                                                    {{ $theme->name }}
                                                </td>
                                                <td class="text-body-secondary">{{ $theme->description ?: 'No description' }}</td>
                                                <td>{{ $theme->updated_at->diffForHumans() }}</td>
                                                <td class="text-end">
                                                    <div class="d-inline-flex gap-2">
                                                        <a class="btn btn-sm btn-outline-primary" href="{{ route('themes.edit', $theme) }}">Edit</a>
                                                        <form method="POST" action="{{ route('themes.destroy', $theme) }}" onsubmit="return confirm('Move this theme to trash?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button class="btn btn-sm btn-outline-danger" type="submit">Trash</button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent">
                        <h2 class="h5 mb-0">Trash</h2>
                    </div>
                    <div class="card-body p-0">
                        @if ($deletedThemes->isEmpty())
                            <p class="text-body-secondary m-0 p-4">Trash is empty.</p>
                        @else
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Deleted</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($deletedThemes as $theme)
                                            <tr>
                                                <td>{{ $theme->name }}</td>
                                                <td>{{ $theme->deleted_at?->diffForHumans() }}</td>
                                                <td class="text-end">
                                                    <form method="POST" action="{{ route('themes.restore', $theme->id) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button class="btn btn-sm btn-outline-success" type="submit">Restore</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
