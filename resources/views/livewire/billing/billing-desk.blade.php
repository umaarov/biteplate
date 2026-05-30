<div>
    @php
        $btnGhost = 'rounded-md border border-neutral-200 px-2.5 py-1.5 text-xs font-medium text-neutral-600 transition hover:bg-neutral-50';
        $input = 'rounded-md border border-neutral-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500';
    @endphp

    <div class="mb-5">
        <h1 class="text-xl font-semibold tracking-tight text-neutral-900">Billing &amp; POS</h1>
        <p class="text-sm text-neutral-500">Itemised bills · tax · tips · split — one facade over a runtime-swappable pricing strategy</p>
    </div>

    @if ($error)
        <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">{{ $error }}</div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Open bills --}}
        <div class="lg:col-span-1">
            <h2 class="mb-2 text-xs font-semibold uppercase tracking-wide text-neutral-400">Open bills</h2>
            <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white divide-y divide-neutral-100">
                @forelse ($billable as $o)
                    <button wire:click="select('{{ $o->id() }}')"
                            class="flex w-full items-center justify-between px-4 py-3 text-left transition hover:bg-neutral-50 {{ $orderId === $o->id() ? 'bg-indigo-50' : '' }}">
                        <div>
                            <div class="font-mono text-sm font-medium text-neutral-900">{{ $o->id() }}</div>
                            <div class="text-xs text-neutral-400">Table {{ $o->tableNumber() }} · {{ $o->status()->label() }}</div>
                        </div>
                        <div class="text-sm font-semibold text-neutral-700">{{ $o->subtotal()->format() }}</div>
                    </button>
                @empty
                    <div class="px-4 py-10 text-center text-sm text-neutral-400">No open bills.</div>
                @endforelse
            </div>
        </div>

        {{-- Bill detail --}}
        <div class="lg:col-span-2">
            @if ($bill)
                <div class="rounded-xl border border-neutral-200 bg-white p-5 shadow-sm">
                    <div class="mb-4 flex items-start justify-between">
                        <div>
                            <div class="font-mono text-sm text-neutral-500">{{ $bill->orderId }}</div>
                            <div class="text-lg font-semibold text-neutral-900">Table {{ $bill->tableNumber }}</div>
                        </div>
                        <div class="flex flex-wrap items-end gap-2">
                            <div>
                                <label class="block text-[11px] font-medium text-neutral-500">Pricing</label>
                                <select wire:model.live="strategy" class="{{ $input }} mt-0.5 py-1.5 text-xs">
                                    @foreach ($strategies as $key => $label)
                                        <option value="{{ $key }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] font-medium text-neutral-500">Tip %</label>
                                <select wire:model.live="tip" class="{{ $input }} mt-0.5 py-1.5 text-xs">
                                    <option value="0">0%</option><option value="10">10%</option>
                                    <option value="13">12.5%</option><option value="15">15%</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] font-medium text-neutral-500">Split</label>
                                <input type="number" min="1" wire:model.live="splitWays" class="{{ $input }} mt-0.5 w-16 py-1.5 text-xs">
                            </div>
                        </div>
                    </div>

                    <div class="divide-y divide-neutral-100 border-y border-neutral-100">
                        @foreach ($bill->lineItems as $li)
                            <div class="flex items-center justify-between py-2 text-sm">
                                <span class="text-neutral-700">{{ $li->label() }}</span>
                                <span class="font-medium text-neutral-700">{{ $li->amount->format() }}</span>
                            </div>
                        @endforeach
                    </div>

                    <dl class="mt-3 space-y-1.5 text-sm">
                        <div class="flex justify-between"><dt class="text-neutral-500">Subtotal</dt><dd class="text-neutral-700">{{ $bill->subtotal->format() }}</dd></div>
                        @unless ($bill->discount->isZero())
                            <div class="flex justify-between text-emerald-700">
                                <dt>{{ $bill->discount->isNegative() ? 'Surcharge' : 'Discount' }} · {{ $bill->pricingStrategy }}</dt>
                                <dd>{{ $bill->discount->multiply(-1)->format() }}</dd>
                            </div>
                        @endunless
                        <div class="flex justify-between"><dt class="text-neutral-500">VAT @ {{ rtrim(rtrim(number_format($bill->taxRatePercent, 1), '0'), '.') }}%</dt><dd class="text-neutral-700">{{ $bill->tax->format() }}</dd></div>
                        @unless ($bill->tip->isZero())
                            <div class="flex justify-between"><dt class="text-neutral-500">Tip</dt><dd class="text-neutral-700">{{ $bill->tip->format() }}</dd></div>
                        @endunless
                        <div class="flex justify-between border-t border-neutral-200 pt-2 text-base font-semibold">
                            <dt class="text-neutral-900">Total</dt><dd class="text-neutral-900">{{ $bill->total->format() }}</dd>
                        </div>
                    </dl>

                    @if ($bill->splitWays > 1)
                        <div class="mt-3 rounded-lg bg-neutral-50 p-3">
                            <div class="mb-1 text-xs font-medium text-neutral-500">Split {{ $bill->splitWays }} ways</div>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($bill->splitShares as $i => $share)
                                    <span class="rounded-md border border-neutral-200 bg-white px-2.5 py-1 text-sm">Guest {{ $i + 1 }}: <span class="font-semibold">{{ $share->format() }}</span></span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @foreach ($bill->notes as $note)
                        <p class="mt-2 text-xs text-neutral-500">• {{ $note }}</p>
                    @endforeach

                    @if ($canClose)
                        <div class="mt-5 flex gap-2">
                            <button wire:click="finalize" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-500">Issue bill</button>
                            <button wire:click="closeTable({{ $bill->tableNumber }})" class="{{ $btnGhost }} px-4 py-2.5 text-sm">Settle &amp; clear table</button>
                        </div>
                    @else
                        <p class="mt-5 rounded-md bg-neutral-50 px-3 py-2 text-xs text-neutral-500">Your role can view this bill but not close it.</p>
                    @endif
                </div>
            @else
                <div class="flex h-64 items-center justify-center rounded-xl border border-dashed border-neutral-200 text-sm text-neutral-400">
                    Select an open bill to begin.
                </div>
            @endif
        </div>
    </div>
</div>
