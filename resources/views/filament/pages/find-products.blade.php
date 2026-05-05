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
            <button
                type="button"
                class="fi-btn fi-btn-color-gray fi-color-gray fi-size-md fi-btn-size-md relative inline-grid grid-flow-col items-center justify-center gap-1.5 rounded-lg bg-white px-3 py-2 text-sm font-semibold text-gray-950 shadow-sm ring-1 ring-gray-950/10 transition duration-75 outline-none hover:bg-gray-50 focus-visible:ring-2 focus-visible:ring-gray-400/40 dark:bg-white/5 dark:text-white dark:ring-white/20 dark:hover:bg-white/10"
                onclick="window.dispatchEvent(new CustomEvent('toggle-scanner'))"
                title="Scan barcode with camera"
            >
                <x-filament::icon
                    icon="heroicon-o-camera"
                    class="fi-btn-icon h-5 w-5 text-gray-400 transition duration-75 dark:text-gray-500"
                />
                <span class="fi-btn-label">
                    Scan
                </span>
            </button>

            {{-- Manual search button --}}
            <x-filament::button wire:click="searchByUpc">
                Search
            </x-filament::button>

        </div>

        {{-- Camera preview (hidden until activated) --}}
        <div id="scanner-container" class="mt-4 hidden">
            <div class="relative w-full overflow-hidden rounded-lg bg-black" style="max-width: 44rem; height: clamp(18rem, 68dvh, 34rem);">
                <video id="scanner-video" class="absolute inset-0 w-full h-full object-cover" autoplay muted playsinline></video>
                {{-- Rectangular barcode guide --}}
                <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                    <div style="width:88%;height:24%;border:2px solid rgba(255,255,255,0.9);border-radius:6px;box-shadow:0 0 0 9999px rgba(0,0,0,0.35);"></div>
                </div>
            </div>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                Align the barcode inside the box.
            </p>
            <button
                type="button"
                class="fi-btn fi-btn-color-danger fi-color-danger fi-size-sm fi-btn-size-sm relative mt-2 inline-grid grid-flow-col items-center justify-center gap-1 rounded-lg bg-danger-600 px-2.5 py-1.5 text-sm font-semibold text-white shadow-sm transition duration-75 outline-none hover:bg-danger-500 focus-visible:ring-2 focus-visible:ring-danger-500/50 dark:bg-danger-500 dark:hover:bg-danger-400 dark:focus-visible:ring-danger-400/50"
                onclick="window.dispatchEvent(new CustomEvent('stop-scanner'))"
            >
                Stop Camera
            </button>
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

            @if ($product['has_multiple_upcs'])
                <div class="mt-5 rounded-lg bg-warning-50 p-4 ring-1 ring-warning-200 dark:bg-warning-400/10 dark:ring-warning-400/30">
                    <p class="font-medium text-warning-900 dark:text-warning-300">
                        This product shares multiple UPCs. Do you want to create a new product?
                    </p>

                    <form wire:submit="createProductFromScannedUpc" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                        <div class="flex-1">
                            <label for="new-product-name" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-200">
                                Name
                            </label>
                            <x-filament::input.wrapper :valid="! $errors->has('newProductName')">
                                <x-filament::input
                                    id="new-product-name"
                                    type="text"
                                    wire:model="newProductName"
                                    maxlength="18"
                                />
                            </x-filament::input.wrapper>
                            @error('newProductName')
                                <p class="mt-1 text-sm text-danger-600 dark:text-danger-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <x-filament::button type="submit" icon="heroicon-o-plus">
                            Create
                        </x-filament::button>
                    </form>
                </div>
            @endif
        </div>
    @endif

    {{-- Result: not found --}}
    @if ($notFound)
        <div class="rounded-xl bg-warning-50 ring-1 ring-warning-200 dark:bg-warning-400/10 dark:ring-warning-400/30 p-6">
            <p class="text-warning-800 dark:text-warning-400 font-medium">
                No product found for UPC <span class="font-mono">{{ $upc }}</span>.
            </p>

            @if (! $copyMode)
                <div class="mt-4 flex flex-wrap gap-3">
                    <x-filament::button
                        tag="a"
                        :href="route('filament.admin.resources.skus.create') . '?upc=' . urlencode($upc)"
                        icon="heroicon-o-plus-circle"
                        color="primary"
                    >
                        Create New Product with This UPC
                    </x-filament::button>

                    <x-filament::button
                        wire:click="startCopyMode"
                        icon="heroicon-o-document-duplicate"
                        color="gray"
                    >
                        Copy from Existing Product
                    </x-filament::button>
                </div>
            @endif
        </div>
    @endif

    {{-- Copy mode: scan a source product --}}
    @if ($copyMode)
        <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
            <h3 class="text-base font-semibold text-gray-900 dark:text-white mb-1">
                Copy from an Existing Product
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                Scan or type the UPC of the product you want to copy. All its fields will be pre-filled on the create form, with the UPC replaced by <span class="font-mono font-medium">{{ $upc }}</span>.
            </p>

            <div class="flex gap-3">
                <div class="flex-1">
                    <x-filament::input.wrapper>
                        <x-filament::input
                            type="text"
                            wire:model="copySourceUpc"
                            wire:keydown.enter="searchCopySource"
                            placeholder="Type or scan a UPC barcode…"
                            maxlength="13"
                        />
                    </x-filament::input.wrapper>
                </div>

                <button
                    type="button"
                    class="fi-btn fi-btn-color-gray fi-color-gray fi-size-md fi-btn-size-md relative inline-grid grid-flow-col items-center justify-center gap-1.5 rounded-lg bg-white px-3 py-2 text-sm font-semibold text-gray-950 shadow-sm ring-1 ring-gray-950/10 transition duration-75 outline-none hover:bg-gray-50 focus-visible:ring-2 focus-visible:ring-gray-400/40 dark:bg-white/5 dark:text-white dark:ring-white/20 dark:hover:bg-white/10"
                    onclick="window.dispatchEvent(new CustomEvent('toggle-scanner'))"
                    title="Scan barcode with camera"
                >
                    <x-filament::icon
                        icon="heroicon-o-camera"
                        class="fi-btn-icon h-5 w-5 text-gray-400 transition duration-75 dark:text-gray-500"
                    />
                    <span class="fi-btn-label">Scan</span>
                </button>

                <x-filament::button wire:click="searchCopySource">
                    Search
                </x-filament::button>
            </div>

            @if ($copySourceNotFound)
                <p class="mt-3 text-sm font-medium text-danger-600 dark:text-danger-400">
                    No product found for that UPC. Try a different one.
                </p>
            @endif

            @if ($copySourceProduct !== null)
                <div class="mt-4 rounded-lg bg-gray-50 dark:bg-white/5 ring-1 ring-gray-200 dark:ring-white/10 p-4 flex items-center justify-between gap-4">
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider mb-0.5">
                            Item #{{ $copySourceProduct['item_number'] }}
                        </p>
                        <p class="text-base font-semibold text-gray-900 dark:text-white">
                            {{ $copySourceProduct['english_description'] }}
                        </p>
                        <p class="text-sm text-primary-600 dark:text-primary-400 font-medium">
                            ${{ number_format($copySourceProduct['price'], 2) }}
                        </p>
                    </div>
                    <x-filament::button
                        wire:click="navigateToCreateWithCopy"
                        icon="heroicon-o-document-duplicate"
                        color="primary"
                    >
                        Copy to New Product
                    </x-filament::button>
                </div>
            @endif

            <div class="mt-4">
                <x-filament::button wire:click="cancelCopyMode" color="gray" icon="heroicon-o-x-mark">
                    Cancel
                </x-filament::button>
            </div>
        </div>
    @endif

    @vite('resources/js/barcode-scanner.js')

</x-filament-panels::page>
