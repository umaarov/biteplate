<div wire:poll.3s="refreshFeed" x-data="{ open: false }" class="relative">
    <button @click="open = !open"
            class="relative rounded-md p-1.5 text-neutral-400 transition hover:bg-neutral-100 hover:text-neutral-700">
        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.85 23.85 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>
        </svg>
        @if (count($items) > 0)
            <span class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-indigo-600 px-1 text-[10px] font-semibold text-white">{{ count($items) }}</span>
        @endif
    </button>

    <div x-show="open" x-cloak @click.outside="open = false" x-transition
         class="absolute right-0 mt-2 w-80 overflow-hidden rounded-lg border border-neutral-200 bg-white shadow-lg">
        <div class="flex items-center justify-between border-b border-neutral-100 px-3 py-2">
            <span class="text-xs font-semibold uppercase tracking-wide text-neutral-500">Notifications</span>
            <span class="text-[11px] text-neutral-400">live</span>
        </div>
        <div class="max-h-96 divide-y divide-neutral-100 overflow-y-auto">
            @forelse ($items as $item)
                <div class="px-3 py-2.5">
                    <div class="mb-0.5 text-[10px] font-medium uppercase tracking-wide
                                {{ str_contains($item['message'], 'ALLERGEN') || str_contains($item['message'], 'WASTE') ? 'text-red-500' : 'text-indigo-500' }}">
                        {{ $item['audience'] }}
                    </div>
                    <div class="text-[13px] leading-snug text-neutral-700">{{ $item['message'] }}</div>
                </div>
            @empty
                <div class="px-3 py-6 text-center text-sm text-neutral-400">No notifications yet</div>
            @endforelse
        </div>
    </div>
</div>
