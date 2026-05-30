<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in · BitePlate</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex h-full items-center justify-center bg-neutral-50 px-4 text-neutral-800">
    <div class="w-full max-w-sm">
        <div class="mb-8 flex flex-col items-center gap-2">
            <span class="inline-flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-600 text-lg font-bold text-white">B</span>
            <h1 class="text-lg font-semibold tracking-tight text-neutral-900">BitePlate SRMS</h1>
            <p class="text-sm text-neutral-500">Smart Restaurant Management System</p>
        </div>

        <div class="rounded-xl border border-neutral-200 bg-white p-6 shadow-sm">
            @if ($errors->any())
                <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif

            @if ($keycloakEnabled)
                <a href="{{ route('auth.keycloak') }}"
                   class="flex w-full items-center justify-center gap-2 rounded-lg bg-neutral-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-neutral-800">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2 4 6v6c0 5 3.4 8.5 8 10 4.6-1.5 8-5 8-10V6l-8-4Z"/></svg>
                    Sign in with Keycloak
                </a>
                @if ($devLogin)
                    <div class="my-5 flex items-center gap-3 text-[11px] uppercase tracking-wide text-neutral-400">
                        <span class="h-px flex-1 bg-neutral-200"></span>or demo role<span class="h-px flex-1 bg-neutral-200"></span>
                    </div>
                @endif
            @endif

            @if ($devLogin)
                <p class="mb-3 text-xs text-neutral-500">Choose a role to explore the system (development sign-in).</p>
                <div class="grid grid-cols-2 gap-2">
                    @foreach ($roles as $role)
                        <form method="POST" action="{{ route('login.dev') }}">
                            @csrf
                            <input type="hidden" name="role" value="{{ $role->value }}">
                            <button class="w-full rounded-lg border border-neutral-200 px-3 py-2.5 text-sm font-medium text-neutral-700 transition hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700">
                                {{ $role->label() }}
                            </button>
                        </form>
                    @endforeach
                </div>
            @endif
        </div>

        <p class="mt-6 text-center text-xs text-neutral-400">Unit 27 · Advanced Programming · BTEC Level 5</p>
    </div>
</body>
</html>
