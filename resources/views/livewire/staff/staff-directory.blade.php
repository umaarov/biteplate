<div>
    <div class="mb-5">
        <h1 class="text-xl font-semibold tracking-tight text-neutral-900">Staff</h1>
        <p class="text-sm text-neutral-500">Directory federated from OpenLDAP · role-based permissions enforced across the system</p>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div>
            <div class="mb-2 flex items-center gap-2">
                <h2 class="text-xs font-semibold uppercase tracking-wide text-neutral-400">Directory</h2>
                @if ($directory['connected'])
                    <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[11px] font-medium text-emerald-700 ring-1 ring-inset ring-emerald-200">LDAP connected</span>
                @else
                    <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-700 ring-1 ring-inset ring-amber-200">Demo directory (LDAP offline)</span>
                @endif
            </div>
            <div class="overflow-hidden rounded-xl border border-neutral-200 bg-white divide-y divide-neutral-100">
                @foreach ($directory['staff'] as $person)
                    <div class="flex items-center gap-3 px-4 py-3">
                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-indigo-50 text-sm font-semibold text-indigo-700">
                            {{ strtoupper(substr($person['name'], 0, 1)) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="text-sm font-medium text-neutral-900">{{ $person['name'] }}</div>
                            <div class="truncate text-xs text-neutral-400">{{ $person['email'] ?: $person['uid'] }}</div>
                        </div>
                        <span class="rounded-full bg-neutral-100 px-2 py-0.5 text-[11px] font-medium text-neutral-600">{{ $person['role'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        <div>
            <h2 class="mb-2 text-xs font-semibold uppercase tracking-wide text-neutral-400">Role &amp; permission matrix</h2>
            <div class="overflow-x-auto rounded-xl border border-neutral-200 bg-white">
                <table class="min-w-full divide-y divide-neutral-100 text-sm">
                    <thead class="bg-neutral-50 text-left text-[11px] font-medium uppercase tracking-wide text-neutral-400">
                        <tr>
                            <th class="px-3 py-2">Permission</th>
                            @foreach (array_keys($roleMatrix) as $role)
                                <th class="px-3 py-2 text-center">{{ $role }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100">
                        @foreach ($allPermissions as $perm)
                            <tr>
                                <td class="px-3 py-2 text-neutral-600">{{ $perm }}</td>
                                @foreach ($roleMatrix as $perms)
                                    <td class="px-3 py-2 text-center">
                                        @if (in_array($perm, $perms, true))
                                            <span class="text-emerald-600">✓</span>
                                        @else
                                            <span class="text-neutral-300">·</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
