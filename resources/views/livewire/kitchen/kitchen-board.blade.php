<div wire:poll.5s>
    @php
        use App\Domain\Ordering\OrderStatus;
        use App\Domain\Shared\KitchenStation;
        $btn = 'rounded-md px-2.5 py-1.5 text-xs font-medium transition';
        $btnPrimary = $btn.' bg-indigo-600 text-white hover:bg-indigo-500';
        $btnGhost = $btn.' border border-neutral-200 text-neutral-600 hover:bg-neutral-50';
        $btnDanger = $btn.' border border-red-200 text-red-600 hover:bg-red-50';
        $statusBadge = [
            'sent_to_kitchen' => 'bg-sky-50 text-sky-700 ring-sky-200',
            'in_preparation'  => 'bg-amber-50 text-amber-700 ring-amber-200',
            'ready'           => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        ];
    @endphp

    <div class="mb-5 flex items-end justify-between">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-neutral-900">Kitchen</h1>
            <p class="text-sm text-neutral-500">Command queue · tickets routed to stations</p>
        </div>
        <button wire:click="undo" @disabled(!$canUndo)
                class="{{ $btnGhost }} disabled:cursor-not-allowed disabled:opacity-40">
            ↶ Undo last action
        </button>
    </div>

    @if ($error)
        <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">{{ $error }}</div>
    @endif

    {{-- Station columns (Scenario E) --}}
    <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach (KitchenStation::cases() as $station)
            @if ($station !== KitchenStation::Pass)
                @php $tickets = $board['stations'][$station->value] ?? []; @endphp
                <div class="rounded-xl border border-neutral-200 bg-white">
                    <div class="flex items-center justify-between border-b border-neutral-100 px-3 py-2">
                        <span class="text-xs font-semibold uppercase tracking-wide text-neutral-500">{{ $station->label() }}</span>
                        <span class="rounded-full bg-neutral-100 px-2 py-0.5 text-[11px] font-medium text-neutral-600">{{ count($tickets) }}</span>
                    </div>
                    <div class="space-y-1.5 p-3">
                        @forelse ($tickets as $ticket)
                            <div class="rounded-md bg-neutral-50 px-2.5 py-1.5">
                                <div class="text-sm text-neutral-800">{{ $ticket->item }}</div>
                                @foreach ($ticket->notes as $note)
                                    <div class="text-[11px] text-amber-700">{{ $note }}</div>
                                @endforeach
                            </div>
                        @empty
                            <div class="py-3 text-center text-xs text-neutral-300">idle</div>
                        @endforelse
                    </div>
                </div>
            @endif
        @endforeach
    </div>

    {{-- Active orders --}}
    <h2 class="mb-2 text-xs font-semibold uppercase tracking-wide text-neutral-400">Active orders</h2>
    <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white divide-y divide-neutral-100">
        @forelse ($board['orders'] as $order)
            <div class="flex items-center gap-4 px-4 py-3">
                <div class="w-28">
                    <div class="font-mono text-sm font-medium text-neutral-900">{{ $order->id() }}</div>
                    <div class="text-xs text-neutral-400">Table {{ $order->tableNumber() }}</div>
                </div>
                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium ring-1 ring-inset {{ $statusBadge[$order->status()->value] ?? 'bg-neutral-100 text-neutral-600 ring-neutral-200' }}">
                    {{ $order->status()->label() }}
                </span>
                <div class="min-w-0 flex-1 text-xs text-neutral-500">
                    {{ count($order->kitchenTickets()) }} ticket(s)
                    @php $allergens = $order->allergens(); @endphp
                    @if (!empty($allergens))
                        · <span class="font-medium text-red-600">allergens: {{ implode(', ', array_map(fn ($a) => $a->label(), $allergens)) }}</span>
                    @endif
                </div>
                <div class="flex gap-1.5">
                    @switch($order->status())
                        @case(OrderStatus::SentToKitchen)
                            <button wire:click="prepare('{{ $order->id() }}')" class="{{ $btnPrimary }}">Prepare</button>
                            <button wire:click="startCancel('{{ $order->id() }}')" class="{{ $btnDanger }}">Cancel</button>
                            @break
                        @case(OrderStatus::InPreparation)
                            <button wire:click="markReady('{{ $order->id() }}')" class="{{ $btnPrimary }}">Mark ready</button>
                            <button wire:click="startCancel('{{ $order->id() }}')" class="{{ $btnDanger }}">Cancel</button>
                            @break
                        @case(OrderStatus::Ready)
                            <button wire:click="serve('{{ $order->id() }}')" class="{{ $btnPrimary }}">Serve</button>
                            @break
                    @endswitch
                </div>
            </div>
        @empty
            <div class="px-4 py-10 text-center text-sm text-neutral-400">The kitchen queue is empty.</div>
        @endforelse
    </div>

    {{-- Cancel modal --}}
    @if ($cancelId !== null)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-neutral-900/40 p-4">
            <div class="w-full max-w-sm rounded-xl border border-neutral-200 bg-white p-5 shadow-xl">
                <h3 class="text-sm font-semibold text-neutral-900">Cancel order {{ $cancelId }}</h3>
                <p class="mt-1 text-xs text-neutral-500">Cancelling after prep begins is logged as waste.</p>
                <input type="text" wire:model="cancelReason" placeholder="Reason"
                       class="mt-3 w-full rounded-md border border-neutral-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <div class="mt-4 flex justify-end gap-2">
                    <button wire:click="$set('cancelId', null)" class="{{ $btnGhost }}">Keep</button>
                    <button wire:click="confirmCancel" class="{{ $btnDanger }}">Cancel order</button>
                </div>
            </div>
        </div>
    @endif
</div>
