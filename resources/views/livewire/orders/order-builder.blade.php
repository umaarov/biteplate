<div>
    @php
        use App\Domain\Menu\MenuCategory;
        $btn = 'rounded-md px-2.5 py-1.5 text-xs font-medium transition';
        $btnPrimary = $btn.' bg-indigo-600 text-white hover:bg-indigo-500';
        $btnGhost = $btn.' border border-neutral-200 text-neutral-600 hover:bg-neutral-50';
        $money = fn (int $minor) => '£'.number_format($minor / 100, 2);
    @endphp

    <div class="mb-5 flex items-center justify-between">
        <div>
            <a href="{{ route('floor') }}" wire:navigate class="text-xs text-neutral-400 hover:text-neutral-600">← Floor</a>
            <h1 class="text-xl font-semibold tracking-tight text-neutral-900">Order · Table {{ $table }}</h1>
            <p class="text-sm text-neutral-500">Draft <span class="font-mono text-neutral-700">{{ $orderId }}</span></p>
        </div>
    </div>

    @if ($error)
        <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">{{ $error }}</div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Menu --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Combos & set meals (Composite) --}}
            @if (!empty($combos))
                <div>
                    <h2 class="mb-2 text-xs font-semibold uppercase tracking-wide text-neutral-400">Combos &amp; Set Meals</h2>
                    <div class="overflow-hidden rounded-xl border border-indigo-100 bg-indigo-50/40 divide-y divide-indigo-100">
                        @foreach ($combos as $combo)
                            <div class="flex items-center gap-3 px-4 py-3">
                                <div class="min-w-0 flex-1">
                                    <div class="text-sm font-medium text-neutral-900">{{ $combo['name'] }}
                                        <span class="ml-1 rounded bg-indigo-100 px-1.5 py-0.5 text-[10px] font-semibold text-indigo-700">−{{ rtrim(rtrim(number_format($combo['discount'], 1), '0'), '.') }}%</span>
                                    </div>
                                    <div class="truncate text-xs text-neutral-400">{{ $combo['description'] }}</div>
                                </div>
                                <div class="text-sm font-semibold text-neutral-700">{{ $money($combo['price_minor']) }}</div>
                                <button wire:click="addCombo('{{ $combo['key'] }}')" class="{{ $btnPrimary }}">Add deal</button>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @foreach ($menuGroups as $categoryValue => $items)
                <div>
                    <h2 class="mb-2 text-xs font-semibold uppercase tracking-wide text-neutral-400">
                        {{ MenuCategory::from($categoryValue)->label() }}
                    </h2>
                    <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white divide-y divide-neutral-100">
                        @foreach ($items as $item)
                            <div class="flex items-center gap-3 px-4 py-3">
                                <div class="min-w-0 flex-1">
                                    <div class="text-sm font-medium text-neutral-900">{{ $item->name }}</div>
                                    <div class="truncate text-xs text-neutral-400">{{ $item->description }}</div>
                                    @if (!empty($item->allergens))
                                        <div class="mt-1 flex flex-wrap gap-1">
                                            @foreach ($item->allergens as $a)
                                                <span class="rounded bg-amber-50 px-1.5 py-0.5 text-[10px] font-medium text-amber-700">{{ $a }}</span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                                <div class="text-sm font-semibold text-neutral-700">{{ $money($item->price_minor) }}</div>
                                <div class="flex gap-1.5">
                                    <button wire:click="openCustomize('{{ $item->sku }}', @js($item->name))" class="{{ $btnGhost }}">Customise</button>
                                    <button wire:click="addQuick('{{ $item->sku }}')" class="{{ $btnPrimary }}">Add</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Order summary --}}
        <div class="lg:col-span-1">
            <div class="sticky top-20 rounded-xl border border-neutral-200 bg-white p-4 shadow-sm">
                <h2 class="mb-3 text-sm font-semibold text-neutral-900">Current order</h2>

                @if ($order && count($order->items()))
                    <div class="space-y-3">
                        @foreach ($order->items() as $i => $line)
                            <div class="flex items-start gap-2">
                                <div class="min-w-0 flex-1">
                                    <div class="text-sm font-medium text-neutral-800">{{ $line->quantity }}× {{ $line->component->name() }}</div>
                                    @php $lines = explode("\n", $line->component->summary()); @endphp
                                    @if (count($lines) > 1)
                                        <div class="text-[11px] leading-snug text-neutral-400">
                                            {!! nl2br(e(implode("\n", array_slice($lines, 1)))) !!}
                                        </div>
                                    @endif
                                </div>
                                <div class="text-sm font-semibold text-neutral-700">{{ $line->lineTotal()->format() }}</div>
                                <button wire:click="removeItem({{ $i }})" class="text-neutral-300 hover:text-red-500" title="Remove">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                </button>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-4 flex items-center justify-between border-t border-neutral-100 pt-3">
                        <span class="text-sm text-neutral-500">Subtotal</span>
                        <span class="text-base font-semibold text-neutral-900">{{ $order->subtotal()->format() }}</span>
                    </div>
                    <button wire:click="send" class="mt-4 w-full rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-500">
                        Send to kitchen
                    </button>
                @else
                    <p class="py-8 text-center text-sm text-neutral-400">No items yet.<br>Add dishes from the menu.</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Customise panel --}}
    @if ($selectedSku !== null)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-neutral-900/40 p-4">
            <div class="w-full max-w-md rounded-xl border border-neutral-200 bg-white p-5 shadow-xl">
                <h3 class="text-sm font-semibold text-neutral-900">Customise · {{ $selectedName }}</h3>

                <div class="mt-4">
                    <div class="mb-1 text-xs font-medium text-neutral-500">Extras</div>
                    <div class="grid grid-cols-2 gap-1.5">
                        @foreach ($extrasCatalog as $extra)
                            <label class="flex items-center gap-2 rounded-md border border-neutral-200 px-2.5 py-1.5 text-sm">
                                <input type="checkbox" wire:model="extras" value="{{ $extra['key'] }}" class="rounded border-neutral-300 text-indigo-600 focus:ring-indigo-500">
                                <span class="flex-1 text-neutral-700">{{ $extra['label'] }}</span>
                                <span class="text-xs text-neutral-400">+£{{ number_format($extra['surcharge_minor'] / 100, 2) }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                @if (!empty($substitutionOptions))
                    <div class="mt-4">
                        <div class="mb-1 text-xs font-medium text-neutral-500">Substitutions</div>
                        <div class="grid grid-cols-1 gap-1.5">
                            @foreach ($substitutionOptions as $sub)
                                <label class="flex items-center gap-2 rounded-md border border-neutral-200 px-2.5 py-1.5 text-sm">
                                    <input type="checkbox" wire:model="subs" value="{{ $sub['key'] }}" class="rounded border-neutral-300 text-indigo-600 focus:ring-indigo-500">
                                    <span class="flex-1 text-neutral-700">{{ $sub['from'] }} → {{ $sub['to'] }}</span>
                                    <span class="text-xs text-neutral-400">{{ $sub['delta_minor'] === 0 ? 'no charge' : '+£'.number_format($sub['delta_minor'] / 100, 2) }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="mt-4">
                    <div class="mb-1 text-xs font-medium text-neutral-500">Allergen requirements (no…)</div>
                    <div class="flex flex-wrap gap-1.5">
                        @foreach ($allergenOptions as $allergen)
                            <label class="flex items-center gap-1.5 rounded-full border border-neutral-200 px-2.5 py-1 text-xs">
                                <input type="checkbox" wire:model="avoid" value="{{ $allergen->value }}" class="rounded border-neutral-300 text-indigo-600 focus:ring-indigo-500">
                                {{ $allergen->label() }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-3 gap-3">
                    <div class="col-span-2">
                        <label class="mb-1 block text-xs font-medium text-neutral-500">Special request</label>
                        <input type="text" wire:model="special" placeholder="e.g. medium rare"
                               class="w-full rounded-md border border-neutral-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-neutral-500">Qty</label>
                        <input type="number" min="1" wire:model="qty"
                               class="w-full rounded-md border border-neutral-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>

                <div class="mt-5 flex justify-end gap-2">
                    <button wire:click="cancelCustomize" class="{{ $btnGhost }}">Cancel</button>
                    <button wire:click="addCustom" class="{{ $btnPrimary }}">Add to order</button>
                </div>
            </div>
        </div>
    @endif
</div>
