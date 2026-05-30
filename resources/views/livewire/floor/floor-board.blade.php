<div>
    @php
        use App\Domain\Tables\TableStatus;
        use App\Domain\Staff\Permission;
        $badge = [
            'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'amber'   => 'bg-amber-50 text-amber-700 ring-amber-200',
            'sky'     => 'bg-sky-50 text-sky-700 ring-sky-200',
            'violet'  => 'bg-violet-50 text-violet-700 ring-violet-200',
            'zinc'    => 'bg-zinc-100 text-zinc-600 ring-zinc-200',
        ];
        $dot = [
            'emerald' => 'bg-emerald-500', 'amber' => 'bg-amber-500', 'sky' => 'bg-sky-500',
            'violet' => 'bg-violet-500', 'zinc' => 'bg-zinc-400',
        ];
        $btn = 'rounded-md px-2.5 py-1.5 text-xs font-medium transition';
        $btnPrimary = $btn.' bg-indigo-600 text-white hover:bg-indigo-500';
        $btnGhost = $btn.' border border-neutral-200 text-neutral-600 hover:bg-neutral-50';
    @endphp

    <div class="mb-5 flex items-end justify-between">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-neutral-900">Floor</h1>
            <p class="text-sm text-neutral-500">Table lifecycle: Free → Reserved → Occupied → Awaiting Bill → Cleared</p>
        </div>
        <div class="flex items-center gap-3 text-xs text-neutral-500">
            @foreach (TableStatus::cases() as $st)
                <span class="inline-flex items-center gap-1.5">
                    <span class="h-2 w-2 rounded-full {{ $dot[$st->tone()] }}"></span>{{ $st->label() }}
                </span>
            @endforeach
        </div>
    </div>

    @if ($error)
        <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">{{ $error }}</div>
    @endif

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
        @foreach ($tables as $table)
            @php $tone = $table->status()->tone(); $status = $table->status(); @endphp
            <div class="rounded-xl border border-neutral-200 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="text-base font-semibold text-neutral-900">Table {{ $table->number() }}</div>
                        <div class="text-xs text-neutral-400">{{ $table->section() ?? '—' }} · {{ $table->capacity() }} seats
                            @if ($table->partySize()) · party of {{ $table->partySize() }} @endif
                        </div>
                    </div>
                    <span class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-[11px] font-medium ring-1 ring-inset {{ $badge[$tone] }}">
                        <span class="h-1.5 w-1.5 rounded-full {{ $dot[$tone] }}"></span>{{ $status->label() }}
                    </span>
                </div>

                <div class="mt-4 flex flex-wrap gap-1.5">
                    @switch($status)
                        @case(TableStatus::Free)
                            <button wire:click="startSeat({{ $table->number() }})" class="{{ $btnPrimary }}">Seat</button>
                            <button wire:click="reserve({{ $table->number() }})" class="{{ $btnGhost }}">Reserve</button>
                            @break
                        @case(TableStatus::Reserved)
                            <button wire:click="startSeat({{ $table->number() }})" class="{{ $btnPrimary }}">Seat</button>
                            <button wire:click="free({{ $table->number() }})" class="{{ $btnGhost }}">Cancel</button>
                            @break
                        @case(TableStatus::Occupied)
                            @if ($currentStaff?->can(Permission::TakeOrder))
                                <button wire:click="takeOrder({{ $table->number() }})" class="{{ $btnPrimary }}">Take order</button>
                            @endif
                            <button wire:click="requestBill({{ $table->number() }})" class="{{ $btnGhost }}">Request bill</button>
                            @break
                        @case(TableStatus::AwaitingBill)
                            @if ($currentStaff?->can(Permission::ViewBilling))
                                <a href="{{ route('billing', ['table' => $table->number()]) }}" wire:navigate class="{{ $btnPrimary }}">Go to billing</a>
                            @endif
                            <button wire:click="clear({{ $table->number() }})" class="{{ $btnGhost }}">Clear</button>
                            @break
                        @case(TableStatus::Cleared)
                            <button wire:click="free({{ $table->number() }})" class="{{ $btnPrimary }}">Reset to free</button>
                            @break
                    @endswitch
                </div>
            </div>
        @endforeach
    </div>

    {{-- Seat modal --}}
    @if ($seatingTable !== null)
        <div class="fixed inset-0 z-40 flex items-center justify-center bg-neutral-900/40 p-4">
            <div class="w-full max-w-xs rounded-xl border border-neutral-200 bg-white p-5 shadow-xl">
                <h3 class="text-sm font-semibold text-neutral-900">Seat party at table {{ $seatingTable }}</h3>
                <label class="mt-3 block text-xs font-medium text-neutral-500">Party size</label>
                <input type="number" min="1" wire:model="partySize"
                       class="mt-1 w-full rounded-md border border-neutral-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <div class="mt-4 flex justify-end gap-2">
                    <button wire:click="$set('seatingTable', null)" class="{{ $btnGhost }}">Cancel</button>
                    <button wire:click="confirmSeat" class="{{ $btnPrimary }}">Seat guests</button>
                </div>
            </div>
        </div>
    @endif
</div>
