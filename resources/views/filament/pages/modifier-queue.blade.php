<x-filament-panels::page>

    {{-- Scan / Search card --}}
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
        <div class="flex gap-3">
            <div class="flex-1">
                <x-filament::input.wrapper>
                    <x-filament::input
                        type="text"
                        wire:model="upc"
                        wire:keydown.enter="searchByUpc"
                        placeholder="Type or scan a UPC barcode…"
                        maxlength="14"
                        autofocus
                        id="upc-input"
                    />
                </x-filament::input.wrapper>
            </div>

            <button
                type="button"
                class="fi-btn fi-btn-color-gray fi-color-gray fi-size-md fi-btn-size-md relative inline-grid grid-flow-col items-center justify-center gap-1.5 rounded-lg bg-white px-3 py-2 text-sm font-semibold text-gray-950 shadow-sm ring-1 ring-gray-950/10 transition duration-75 outline-none hover:bg-gray-50 focus-visible:ring-2 focus-visible:ring-gray-400/40 dark:bg-white/5 dark:text-white dark:ring-white/20 dark:hover:bg-white/10"
                onclick="window.dispatchEvent(new CustomEvent('toggle-scanner'))"
                title="Scan barcode with camera"
            >
                <x-filament::icon icon="heroicon-o-camera" class="fi-btn-icon h-5 w-5 text-gray-400 dark:text-gray-500" />
                <span class="fi-btn-label">Scan</span>
            </button>

            <x-filament::button wire:click="searchByUpc">
                Add to Queue
            </x-filament::button>
        </div>

        {{-- Inline camera preview --}}
        <div id="scanner-container" class="mt-4 hidden">
            <div class="relative w-full overflow-hidden rounded-lg bg-black" style="max-width: 44rem; height: clamp(18rem, 68dvh, 34rem);">
                <video id="scanner-video" class="absolute inset-0 w-full h-full object-cover" autoplay muted playsinline></video>
                <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                    <div style="width:88%;height:24%;border:2px solid rgba(255,255,255,0.9);border-radius:6px;box-shadow:0 0 0 9999px rgba(0,0,0,0.35);"></div>
                </div>
            </div>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Align the barcode inside the box.</p>
            <button
                type="button"
                class="fi-btn fi-btn-color-danger fi-color-danger fi-size-sm fi-btn-size-sm relative mt-2 inline-grid grid-flow-col items-center justify-center gap-1 rounded-lg bg-danger-600 px-2.5 py-1.5 text-sm font-semibold text-white shadow-sm transition duration-75 outline-none hover:bg-danger-500"
                onclick="window.dispatchEvent(new CustomEvent('stop-scanner'))"
            >
                Stop Camera
            </button>
        </div>

        {{-- Scan feedback --}}
        @if ($scanNotFound)
            <p class="mt-3 text-sm font-medium text-danger-600 dark:text-danger-400">
                No product found for UPC <span class="font-mono">{{ $upc }}</span>.
            </p>
        @endif
    </div>

    {{-- Queue --}}
    <div
        class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10"
        x-data="{
            showDeptModal: false,
            selectedDept: '',
            showPriceGroupModal: false,
            selectedPriceGroup: '',
            showPriceModal: false,
            newPrice: '',
        }"
    >
        @if ($queueItems->isEmpty())
            <div class="p-10 text-center text-gray-400 dark:text-gray-500">
                <x-filament::icon icon="heroicon-o-pencil-square" class="mx-auto h-10 w-10 mb-3 opacity-40" />
                <p class="text-sm">Your modifier queue is empty. Scan a product to get started.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 dark:border-white/10 text-left">
                            <th class="px-4 py-3 w-10">
                                <input
                                    type="checkbox"
                                    class="rounded border-gray-300 dark:border-gray-600"
                                    @if (count($selectedItems) === $queueItems->count() && $queueItems->count() > 0) checked @endif
                                    wire:click="$set('selectedItems', {{ count($selectedItems) === $queueItems->count() ? '[]' : $queueItems->pluck('id')->toJson() }})"
                                />
                            </th>
                            <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-200">Product Name</th>
                            <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-200">Item #</th>
                            <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-200">UPC</th>
                            <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-200">Department</th>
                            <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-200">Price Group</th>
                            <th class="px-4 py-3 font-semibold text-gray-700 dark:text-gray-200 text-right">Price</th>
                            <th class="px-4 py-3 w-10"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50 dark:divide-white/5">
                        @foreach ($queueItems as $item)
                            <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition">
                                <td class="px-4 py-3">
                                    <input
                                        type="checkbox"
                                        class="rounded border-gray-300 dark:border-gray-600"
                                        value="{{ $item->id }}"
                                        wire:model="selectedItems"
                                    />
                                </td>
                                <td class="px-4 py-3 font-medium text-gray-900 dark:text-white">
                                    {{ trim($item->sku?->english_description ?? '—') }}
                                </td>
                                <td class="px-4 py-3 font-mono text-gray-500 dark:text-gray-400 text-xs">
                                    {{ $item->item_number }}
                                </td>
                                <td class="px-4 py-3 font-mono text-gray-500 dark:text-gray-400 text-xs">
                                    {{ $item->sku?->upcs->first()?->upc ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300 text-xs">
                                    {{ $item->sku?->department?->description ?? $item->sku?->department_number ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300 text-xs">
                                    {{ $item->sku?->priceGroup?->english_description ?? ($item->sku?->price_group_number ? $item->sku->price_group_number : '—') }}
                                </td>
                                <td class="px-4 py-3 text-right font-semibold text-primary-600 dark:text-primary-400">
                                    ${{ number_format($item->sku?->price ?? 0, 2) }}
                                </td>
                                <td class="px-4 py-3">
                                    <button
                                        type="button"
                                        wire:click="removeItem({{ $item->id }})"
                                        class="text-gray-400 hover:text-danger-500 dark:text-gray-500 dark:hover:text-danger-400 transition"
                                        title="Remove"
                                    >
                                        <x-filament::icon icon="heroicon-o-trash" class="h-4 w-4" />
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Footer actions --}}
            <div class="flex items-center justify-between gap-4 px-4 py-3 border-t border-gray-100 dark:border-white/10">
                <p class="text-xs text-gray-400 dark:text-gray-500">
                    {{ count($selectedItems) }} of {{ $queueItems->count() }} selected
                </p>
                <div class="flex flex-wrap gap-2">
                    <x-filament::button
                        color="gray"
                        wire:click="clearQueue"
                        wire:confirm="Clear all items from the queue?"
                        icon="heroicon-o-trash"
                        size="sm"
                    >
                        Clear Queue
                    </x-filament::button>

                    {{-- Change Department --}}
                    <x-filament::button
                        color="warning"
                        icon="heroicon-o-building-storefront"
                        size="sm"
                        :disabled="empty($selectedItems)"
                        x-on:click="showDeptModal = true; selectedDept = ''"
                    >
                        Change Department
                    </x-filament::button>

                    {{-- Change Price Group --}}
                    <x-filament::button
                        color="warning"
                        icon="heroicon-o-currency-dollar"
                        size="sm"
                        :disabled="empty($selectedItems)"
                        x-on:click="showPriceGroupModal = true; selectedPriceGroup = ''"
                    >
                        Change Price Group
                    </x-filament::button>

                    {{-- Change Price --}}
                    <x-filament::button
                        color="warning"
                        icon="heroicon-o-banknotes"
                        size="sm"
                        :disabled="empty($selectedItems)"
                        x-on:click="showPriceModal = true; newPrice = ''"
                    >
                        Change Price
                    </x-filament::button>

                    {{-- Toggle Active --}}
                    <x-filament::button
                        color="success"
                        icon="heroicon-o-check-circle"
                        size="sm"
                        :disabled="empty($selectedItems)"
                        wire:click="applyToggleActive(true)"
                        wire:confirm="Mark selected items as Active?"
                    >
                        Set Active
                    </x-filament::button>

                    <x-filament::button
                        color="danger"
                        icon="heroicon-o-x-circle"
                        size="sm"
                        :disabled="empty($selectedItems)"
                        wire:click="applyToggleActive(false)"
                        wire:confirm="Mark selected items as Inactive?"
                    >
                        Set Inactive
                    </x-filament::button>
                </div>
            </div>

            {{-- Change Department Modal --}}
            <div
                x-show="showDeptModal"
                x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center"
                x-on:keydown.escape.window="showDeptModal = false"
            >
                <div class="absolute inset-0 bg-black/50" x-on:click="showDeptModal = false"></div>
                <div class="relative z-10 w-full max-w-sm rounded-xl bg-white dark:bg-gray-900 shadow-xl p-6 ring-1 ring-gray-950/5 dark:ring-white/10">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-4">Change Department</h2>
                    <select
                        x-model="selectedDept"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-500"
                    >
                        <option value="">— Select a department —</option>
                        @foreach ($departments as $number => $description)
                            <option value="{{ $number }}">{{ $description }} ({{ $number }})</option>
                        @endforeach
                    </select>
                    <div class="flex justify-end gap-3 mt-5">
                        <x-filament::button color="gray" size="sm" x-on:click="showDeptModal = false">
                            Cancel
                        </x-filament::button>
                        <x-filament::button
                            size="sm"
                            x-on:click="if (selectedDept) { $wire.applyChangeDepartment(selectedDept); showDeptModal = false; }"
                            x-bind:disabled="!selectedDept"
                        >
                            Apply
                        </x-filament::button>
                    </div>
                </div>
            </div>

            {{-- Change Price Group Modal --}}
            <div
                x-show="showPriceGroupModal"
                x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center"
                x-on:keydown.escape.window="showPriceGroupModal = false"
            >
                <div class="absolute inset-0 bg-black/50" x-on:click="showPriceGroupModal = false"></div>
                <div class="relative z-10 w-full max-w-sm rounded-xl bg-white dark:bg-gray-900 shadow-xl p-6 ring-1 ring-gray-950/5 dark:ring-white/10">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-4">Change Price Group</h2>
                    <select
                        x-model="selectedPriceGroup"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-500"
                    >
                        <option value="">— No price group (clear) —</option>
                        @foreach ($priceGroups as $number => $description)
                            <option value="{{ $number }}">{{ $description }} ({{ $number }})</option>
                        @endforeach
                    </select>
                    <div class="flex justify-end gap-3 mt-5">
                        <x-filament::button color="gray" size="sm" x-on:click="showPriceGroupModal = false">
                            Cancel
                        </x-filament::button>
                        <x-filament::button
                            size="sm"
                            x-on:click="$wire.applyChangePriceGroup(selectedPriceGroup || null); showPriceGroupModal = false;"
                        >
                            Apply
                        </x-filament::button>
                    </div>
                </div>
            </div>

            {{-- Change Price Modal --}}
            <div
                x-show="showPriceModal"
                x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center"
                x-on:keydown.escape.window="showPriceModal = false"
            >
                <div class="absolute inset-0 bg-black/50" x-on:click="showPriceModal = false"></div>
                <div class="relative z-10 w-full max-w-sm rounded-xl bg-white dark:bg-gray-900 shadow-xl p-6 ring-1 ring-gray-950/5 dark:ring-white/10">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-4">Change Price</h2>
                    <div class="flex items-center gap-2">
                        <span class="text-gray-500 dark:text-gray-400 text-sm">$</span>
                        <input
                            type="number"
                            min="0"
                            step="0.01"
                            x-model="newPrice"
                            placeholder="0.00"
                            class="flex-1 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-white px-3 py-2 focus:outline-none focus:ring-2 focus:ring-primary-500"
                        />
                    </div>
                    <div class="flex justify-end gap-3 mt-5">
                        <x-filament::button color="gray" size="sm" x-on:click="showPriceModal = false">
                            Cancel
                        </x-filament::button>
                        <x-filament::button
                            size="sm"
                            x-on:click="if (newPrice !== '') { $wire.applyChangePrice(newPrice); showPriceModal = false; }"
                            x-bind:disabled="newPrice === ''"
                        >
                            Apply
                        </x-filament::button>
                    </div>
                </div>
            </div>

        @endif
    </div>

    @vite('resources/js/barcode-scanner.js')

</x-filament-panels::page>
