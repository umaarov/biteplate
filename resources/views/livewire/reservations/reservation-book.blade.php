<div>
    @php $input = 'mt-1 w-full rounded-md border border-neutral-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500'; @endphp

    <div class="mb-5">
        <h1 class="text-xl font-semibold tracking-tight text-neutral-900">Reservations</h1>
        <p class="text-sm text-neutral-500">Booking triggers a confirmation, a calendar entry, a table hold and a reminder — added independently (Observer pipeline)</p>
    </div>

    @if ($message)
        <div class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">{{ $message }}</div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-1">
            <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm">
                <h2 class="mb-3 text-sm font-semibold text-neutral-900">New booking</h2>
                <div class="space-y-3">
                    <div>
                        <label class="text-xs font-medium text-neutral-500">Customer name</label>
                        <input type="text" wire:model="customerName" class="{{ $input }}">
                        @error('customerName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs font-medium text-neutral-500">Table</label>
                            <select wire:model="tableNumber" class="{{ $input }}">
                                <option value="">Select…</option>
                                @foreach ($tables as $t)
                                    <option value="{{ $t->number }}">#{{ $t->number }} ({{ $t->capacity }} seats)</option>
                                @endforeach
                            </select>
                            @error('tableNumber') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="text-xs font-medium text-neutral-500">Party size</label>
                            <input type="number" min="1" wire:model="partySize" class="{{ $input }}">
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-xs font-medium text-neutral-500">Date</label>
                            <input type="date" wire:model="date" class="{{ $input }}">
                        </div>
                        <div>
                            <label class="text-xs font-medium text-neutral-500">Time</label>
                            <input type="time" wire:model="time" class="{{ $input }}">
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-neutral-500">Phone (for SMS)</label>
                        <input type="text" wire:model="phone" placeholder="+44…" class="{{ $input }}">
                    </div>
                    <button wire:click="book" class="w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-500">
                        Confirm booking
                    </button>
                </div>
            </div>
        </div>

        <div class="lg:col-span-2">
            <h2 class="mb-2 text-xs font-semibold uppercase tracking-wide text-neutral-400">Upcoming</h2>
            <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white divide-y divide-neutral-100">
                @forelse ($upcoming as $r)
                    <div class="flex items-center gap-4 px-4 py-3">
                        <div class="w-16 text-center">
                            <div class="text-sm font-semibold text-neutral-900">{{ $r->starts_at->format('H:i') }}</div>
                            <div class="text-[11px] text-neutral-400">{{ $r->starts_at->format('d M') }}</div>
                        </div>
                        <div class="flex-1">
                            <div class="text-sm font-medium text-neutral-900">{{ $r->customer_name }}</div>
                            <div class="text-xs text-neutral-400">Table {{ $r->table_number }} · party of {{ $r->party_size }} · {{ $r->phone ?? 'no phone' }}</div>
                        </div>
                        <span class="rounded-full bg-neutral-100 px-2 py-0.5 text-[11px] font-medium text-neutral-600">{{ ucfirst($r->status) }}</span>
                    </div>
                @empty
                    <div class="px-4 py-10 text-center text-sm text-neutral-400">No upcoming reservations.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
