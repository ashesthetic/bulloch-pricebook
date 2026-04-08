<x-filament-panels::page>

    {{-- Search card --}}
    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
        <div class="flex gap-3">

            {{-- UPC text input --}}
            <div class="flex-1">
                <x-filament::input.wrapper>
                    <x-filament::input
                        type="text"
                        wire:model="upc"
                        wire:keydown.enter="searchByUpc"
                        placeholder="Type or scan a UPC barcode…"
                        maxlength="13"
                        autofocus
                        id="upc-input"
                    />
                </x-filament::input.wrapper>
            </div>

            {{-- Camera scan button --}}
            <x-filament::button
                icon="heroicon-o-camera"
                color="gray"
                x-data
                x-on:click="$dispatch('toggle-scanner')"
                title="Scan barcode with camera"
            >
                Scan
            </x-filament::button>

            {{-- Manual search button --}}
            <x-filament::button wire:click="searchByUpc">
                Search
            </x-filament::button>

        </div>

        {{-- Camera preview (hidden until activated) --}}
        <div id="scanner-container" class="mt-4 hidden" style="max-width: 400px;">
            <video id="scanner-video" class="w-full rounded-lg" autoplay muted playsinline></video>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                Point the camera at a barcode. It will be detected automatically.
            </p>
            <x-filament::button
                color="danger"
                size="sm"
                class="mt-2"
                x-data
                x-on:click="$dispatch('stop-scanner')"
            >
                Stop Camera
            </x-filament::button>
        </div>
    </div>

    {{-- Result: product found --}}
    @if ($product !== null)
        <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">
                        Item #{{ $product['item_number'] }}
                        &nbsp;&middot;&nbsp;
                        UPC {{ $product['upc'] }}
                    </p>
                    <h2 class="text-2xl font-bold text-gray-950 dark:text-white">
                        {{ $product['english_description'] }}
                    </h2>
                </div>
                <div class="text-right shrink-0">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-1">Price</p>
                    <p class="text-3xl font-bold text-primary-600 dark:text-primary-400">
                        ${{ number_format($product['price'], 2) }}
                    </p>
                </div>
            </div>

            <div class="mt-4 border-t border-gray-100 dark:border-white/10 pt-4">
                <x-filament::link
                    :href="route('filament.admin.resources.skus.edit', ['record' => $product['item_number']])"
                    icon="heroicon-o-pencil-square"
                >
                    Edit this SKU
                </x-filament::link>
            </div>
        </div>
    @endif

    {{-- Result: not found --}}
    @if ($notFound)
        <div class="rounded-xl bg-warning-50 ring-1 ring-warning-200 dark:bg-warning-400/10 dark:ring-warning-400/30 p-6">
            <p class="text-warning-800 dark:text-warning-400 font-medium">
                No product found for UPC <span class="font-mono">{{ $upc }}</span>.
            </p>
        </div>
    @endif

    @vite('resources/js/barcode-scanner.js')

</x-filament-panels::page>
