<div>
    <div class="mb-5">
        <h1 class="text-xl font-semibold tracking-tight text-neutral-900">Reports</h1>
        <p class="text-sm text-neutral-500">Order history &amp; analytics — traversed via the Iterator over the audit Singleton</p>
    </div>

    @php
        $card = 'rounded-xl border border-neutral-200 bg-white p-4 shadow-sm';
    @endphp

    <div class="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-6">
        <div class="{{ $card }}"><div class="text-xs text-neutral-400">Orders</div><div class="mt-1 text-xl font-semibold text-neutral-900">{{ $m['orders'] }}</div></div>
        <div class="{{ $card }}"><div class="text-xs text-neutral-400">Revenue</div><div class="mt-1 text-xl font-semibold text-neutral-900">{{ $m['revenue']->format() }}</div></div>
        <div class="{{ $card }}"><div class="text-xs text-neutral-400">Covers</div><div class="mt-1 text-xl font-semibold text-neutral-900">{{ $m['covers'] }}</div></div>
        <div class="{{ $card }}"><div class="text-xs text-neutral-400">Avg / table</div><div class="mt-1 text-xl font-semibold text-neutral-900">{{ $m['avg_spend_per_table']->format() }}</div></div>
        <div class="{{ $card }}"><div class="text-xs text-neutral-400">Peak hour</div><div class="mt-1 text-xl font-semibold text-neutral-900">{{ $m['peak_hour'] ?? '—' }}</div></div>
        <div class="{{ $card }}"><div class="text-xs text-neutral-400">Waste cancels</div><div class="mt-1 text-xl font-semibold {{ $m['waste'] > 0 ? 'text-red-600' : 'text-neutral-900' }}">{{ $m['waste'] }}</div></div>
    </div>

    <div class="mt-3 grid grid-cols-1 gap-3 lg:grid-cols-3">
        <div class="{{ $card }}">
            <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-neutral-400">Food vs Drinks</div>
            <div class="flex justify-between text-sm"><span class="text-neutral-500">Food</span><span class="font-medium">{{ $m['food']->format() }}</span></div>
            <div class="flex justify-between text-sm"><span class="text-neutral-500">Drinks</span><span class="font-medium">{{ $m['drinks']->format() }}</span></div>
        </div>
        <div class="{{ $card }}">
            <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-neutral-400">Top 10 items</div>
            @forelse ($m['top_items'] as $name => $count)
                <div class="flex justify-between py-0.5 text-sm"><span class="truncate text-neutral-700">{{ $name }}</span><span class="text-neutral-400">{{ $count }}</span></div>
            @empty
                <p class="text-sm text-neutral-400">No data yet.</p>
            @endforelse
        </div>
        <div class="{{ $card }}">
            <div class="mb-2 text-xs font-semibold uppercase tracking-wide text-neutral-400">Top waitstaff (covers)</div>
            @forelse ($m['top_waiters'] as $w)
                <div class="flex justify-between py-0.5 text-sm"><span class="text-neutral-700">{{ $w['staff'] }}</span><span class="text-neutral-400">{{ $w['covers'] }}</span></div>
            @empty
                <p class="text-sm text-neutral-400">No data yet.</p>
            @endforelse
        </div>
    </div>

    <h2 class="mb-2 mt-6 text-xs font-semibold uppercase tracking-wide text-neutral-400">Order history (audit log)</h2>
    <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white">
        <table class="min-w-full divide-y divide-neutral-100 text-sm">
            <thead class="bg-neutral-50 text-left text-xs font-medium uppercase tracking-wide text-neutral-400">
                <tr>
                    <th class="px-4 py-2">Order</th><th class="px-4 py-2">Table</th><th class="px-4 py-2">Staff</th>
                    <th class="px-4 py-2">Strategy</th><th class="px-4 py-2">Placed</th><th class="px-4 py-2 text-right">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-100">
                @forelse ($recent as $row)
                    <tr class="{{ $row->wasteful ? 'bg-red-50' : '' }}">
                        <td class="px-4 py-2 font-mono text-neutral-700">{{ $row->order_id }}</td>
                        <td class="px-4 py-2 text-neutral-600">{{ $row->table_number }}</td>
                        <td class="px-4 py-2 text-neutral-600">{{ $row->staff_id }}</td>
                        <td class="px-4 py-2 text-neutral-600">{{ $row->pricing_strategy }}</td>
                        <td class="px-4 py-2 text-neutral-500">{{ $row->placed_at?->format('d M H:i') }}</td>
                        <td class="px-4 py-2 text-right font-medium text-neutral-800">£{{ number_format($row->total_minor / 100, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-neutral-400">No orders recorded yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
