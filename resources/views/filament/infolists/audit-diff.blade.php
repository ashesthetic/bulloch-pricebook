<x-dynamic-component :component="$getEntryWrapperView()" :entry="$entry">
    @php
        $record = $entry->getRecord();
        $old    = $record->old_values ?? [];
        $new    = $record->new_values ?? [];
        $action = $record->action;
        $keys   = array_unique(array_merge(array_keys($old), array_keys($new)));
    @endphp

    <div class="w-full overflow-x-auto">
        <table class="w-full text-sm divide-y divide-gray-200 dark:divide-white/5">
            <thead>
                <tr>
                    <th class="w-1/4 px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        Field
                    </th>
                    @if ($action === 'updated')
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-red-500">Before</th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-green-500">After</th>
                    @elseif ($action === 'created')
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-green-500">Value</th>
                    @else
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-red-500">Value</th>
                    @endif
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                @foreach ($keys as $key)
                    @php
                        $before = $old[$key] ?? null;
                        $after  = $new[$key] ?? null;

                        if ($action === 'updated' && (string) $before === (string) $after) {
                            continue;
                        }

                        $displayBefore = filled($before) ? \App\Filament\Resources\AuditLogResource::formatValue($before) : null;
                        $displayAfter  = filled($after)  ? \App\Filament\Resources\AuditLogResource::formatValue($after)  : null;
                        $displayValue  = $action === 'deleted' ? $displayBefore : $displayAfter;

                        if ($action !== 'updated' && ! filled($displayValue)) {
                            continue;
                        }
                    @endphp
                    <tr>
                        <td class="w-1/4 px-3 py-1.5 font-medium text-gray-700 dark:text-gray-300">
                            {{ ucwords(str_replace('_', ' ', $key)) }}
                        </td>

                        @if ($action === 'updated')
                            <td class="px-3 py-1.5 text-red-600 dark:text-red-400">
                                @if (filled($displayBefore))
                                    <span class="line-through">{{ $displayBefore }}</span>
                                @else
                                    <em class="text-gray-400">—</em>
                                @endif
                            </td>
                            <td class="px-3 py-1.5 text-green-600 dark:text-green-400">
                                @if (filled($displayAfter))
                                    {{ $displayAfter }}
                                @else
                                    <em class="text-gray-400">—</em>
                                @endif
                            </td>
                        @elseif ($action === 'created')
                            <td class="px-3 py-1.5 text-green-600 dark:text-green-400">{{ $displayAfter }}</td>
                        @else
                            <td class="px-3 py-1.5 text-red-600 dark:text-red-400">{{ $displayBefore }}</td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-dynamic-component>
