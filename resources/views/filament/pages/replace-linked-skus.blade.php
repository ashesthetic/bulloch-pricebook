<x-filament-panels::page>

    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Every distinct linked SKU currently in use is listed below. Enter the new item number you want to
            replace it with — every item that links to the old SKU will be switched to the new one. Leave a
            row blank to skip it.
        </p>
    </div>

    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        @if (empty($rows))
            <div class="p-10 text-center text-gray-400 dark:text-gray-500">
                <x-filament::icon icon="heroicon-o-arrow-path" class="mx-auto h-10 w-10 mb-3 opacity-40" />
                <p class="text-sm">No items currently have a linked SKU.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 dark:border-white/10 text-left">
                            <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-200">Old Linked SKU</th>
                            <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-200">Description</th>
                            <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-200">Used By</th>
                            <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-200">New Item Number</th>
                            <th class="px-4 py-3 w-32"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-white/5">
                        @foreach ($rows as $index => $row)
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition">
                                <td class="px-4 py-3 font-mono text-gray-500 dark:text-gray-400 text-xs">
                                    {{ $row['old_item_number'] }}
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                    {{ $row['old_description'] !== '' ? $row['old_description'] : '—' }}
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                    {{ $row['usage_count'] }} item{{ $row['usage_count'] === 1 ? '' : 's' }}
                                </td>
                                <td class="px-4 py-3">
                                    <input
                                        type="text"
                                        wire:model="rows.{{ $index }}.new_item_number"
                                        placeholder="New item number…"
                                        class="w-48 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-white px-3 py-1.5 font-mono focus:outline-none focus:ring-2 focus:ring-primary-500"
                                    />
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <x-filament::button
                                        size="sm"
                                        color="gray"
                                        wire:click="applyReplacement({{ $index }})"
                                        wire:confirm="Replace linked SKU {{ $row['old_item_number'] }} with the new item number across all {{ $row['usage_count'] }} item(s) that use it?"
                                    >
                                        Replace
                                    </x-filament::button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex items-center justify-end gap-3 px-4 py-3 border-t border-gray-100 dark:border-white/10">
                <x-filament::button
                    color="primary"
                    icon="heroicon-o-arrow-path"
                    wire:click="applyAll"
                    wire:confirm="Apply all rows that have a new item number entered?"
                >
                    Apply All
                </x-filament::button>
            </div>
        @endif
    </div>

</x-filament-panels::page>
