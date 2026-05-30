<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'BitePlate' }} · BitePlate SRMS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-neutral-50 text-neutral-800 antialiased">
@php
    use App\Domain\Staff\Permission;
    $staff = $currentStaff ?? null;
    $nav = [
        ['route' => 'floor',        'label' => 'Floor',        'perm' => Permission::ViewFloor],
        ['route' => 'kitchen',      'label' => 'Kitchen',      'perm' => Permission::ViewKitchenQueue],
        ['route' => 'billing',      'label' => 'Billing',      'perm' => Permission::ViewBilling],
        ['route' => 'reservations', 'label' => 'Reservations', 'perm' => Permission::ViewFloor],
        ['route' => 'history',      'label' => 'Reports',      'perm' => Permission::ViewReports],
        ['route' => 'staff',        'label' => 'Staff',        'perm' => Permission::ManageStaff],
    ];
@endphp

<div class="min-h-full">
    <header class="sticky top-0 z-30 border-b border-neutral-200 bg-white/80 backdrop-blur">
        <div class="mx-auto flex h-14 max-w-7xl items-center gap-6 px-4 sm:px-6">
            <a href="{{ route('floor') }}" class="flex items-center gap-2">
                <span class="inline-flex h-7 w-7 items-center justify-center rounded-md bg-indigo-600 text-sm font-bold text-white">B</span>
                <span class="text-[15px] font-semibold tracking-tight text-neutral-900">BitePlate</span>
                <span class="hidden rounded-full border border-neutral-200 px-2 py-0.5 text-[11px] font-medium text-neutral-500 sm:inline">SRMS</span>
            </a>

            <nav class="flex items-center gap-1">
                @foreach ($nav as $item)
                    @if ($staff?->can($item['perm']))
                        @php $active = request()->routeIs($item['route'].'*'); @endphp
                        <a href="{{ route($item['route']) }}"
                           class="rounded-md px-3 py-1.5 text-sm font-medium transition
                                  {{ $active ? 'bg-neutral-100 text-neutral-900' : 'text-neutral-500 hover:text-neutral-900 hover:bg-neutral-50' }}">
                            {{ $item['label'] }}
                        </a>
                    @endif
                @endforeach
            </nav>

            <div class="ml-auto flex items-center gap-3">
                @livewire('shared.notification-feed')
                @if ($staff)
                    <div class="flex items-center gap-2 border-l border-neutral-200 pl-3">
                        <div class="text-right leading-tight">
                            <div class="text-sm font-medium text-neutral-900">{{ $staff->name() }}</div>
                            <div class="text-[11px] uppercase tracking-wide text-indigo-600">{{ $staff->role()->label() }}</div>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="rounded-md p-1.5 text-neutral-400 hover:bg-neutral-100 hover:text-neutral-700" title="Sign out">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M18 12H9m9 0l-3-3m3 3l-3 3"/></svg>
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    </header>

    @if (session('status'))
        <div class="mx-auto mt-4 max-w-7xl px-4 sm:px-6">
            <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm text-emerald-800">{{ session('status') }}</div>
        </div>
    @endif

    <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6">
        {{ $slot }}
    </main>
</div>
</body>
</html>
