<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Users</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-body-tertiary">
    <main class="container py-5">
        <div class="row justify-content-center">
            <div class="col-12 col-xl-10">
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                    <div>
                        <h1 class="h2 mb-1">
                            <i class="bi bi-people text-primary me-2"></i>
                            Users
                        </h1>
                        <p class="text-body-secondary mb-0">Create, edit, and remove users.</p>
                    </div>

                    <div class="d-flex gap-2">
                        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-speedometer2 me-1"></i>
                            Dashboard
                        </a>
                        <a href="{{ route('presentations.index') }}" class="btn btn-outline-secondary">
                            <i class="bi bi-journal-richtext me-1"></i>
                            Presentations
                        </a>
                        <a href="{{ route('users.create') }}" class="btn btn-primary">
                            <i class="bi bi-person-plus me-1"></i>
                            New User
                        </a>
                    </div>
                </div>

                @if (session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif

                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        @if ($users->isEmpty())
                            <p class="text-body-secondary m-0 p-4">No users found.</p>
                        @else
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Created</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($users as $user)
                                            <tr>
                                                <td>{{ $user->name }}</td>
                                                <td class="text-body-secondary">{{ $user->email }}</td>
                                                <td>{{ $user->created_at?->diffForHumans() }}</td>
                                                <td class="text-end">
                                                    <div class="d-inline-flex gap-2">
                                                        <a class="btn btn-sm btn-outline-primary" href="{{ route('users.edit', $user) }}">Edit</a>
                                                        <form method="POST" action="{{ route('users.destroy', $user) }}" onsubmit="return confirm('Delete this user?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
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
            </div>
        </div>
    </main>
</body>
</html>