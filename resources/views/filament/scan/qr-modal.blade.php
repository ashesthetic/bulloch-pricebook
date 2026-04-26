<div
    x-data="{
        isMobile: /Mobi|Android|iPhone|iPad/i.test(navigator.userAgent),
        pollInterval: null,
        wire: null,
        startPolling() {
            clearInterval(this.pollInterval);
            this.pollInterval = setInterval(() => this.wire?.checkScanToken(), 1500);
        }
    }"
    x-init="
        wire = $wire;
        if (isMobile) {
            $nextTick(() => window.dispatchEvent(new CustomEvent('start-scanner')));
        } else {
            startPolling();
            $wire.$watch('activeScanToken', v => { if (!v) { clearInterval(pollInterval); pollInterval = null; } });
        }
    "
    x-on:scan-ready.window="
        if (isMobile) {
            $nextTick(() => window.dispatchEvent(new CustomEvent('start-scanner')));
        } else {
            startPolling();
        }
    "
    x-on:close-modal.window="
        window.dispatchEvent(new CustomEvent('stop-scanner'));
        clearInterval(pollInterval);
        pollInterval = null;
    "
>
    {{-- Mobile: inline camera --}}
    <div x-show="isMobile">
        <div
            id="scanner-container"
            class="relative overflow-hidden rounded-lg w-full"
            style="aspect-ratio: 16/9; max-width: 400px;"
        >
            <video
                id="scanner-video"
                class="absolute inset-0 w-full h-full object-cover"
                autoplay muted playsinline
            ></video>
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                <div style="width:85%;height:28%;border:2px solid rgba(255,255,255,0.85);border-radius:6px;box-shadow:0 0 0 9999px rgba(0,0,0,0.45);"></div>
            </div>
        </div>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Align the barcode inside the box.</p>
    </div>

    {{-- Desktop: QR code --}}
    <div x-show="!isMobile">
        <div class="flex flex-col items-center gap-4 py-2">
            <div class="rounded-xl bg-white p-3 shadow-sm">
                {!! $qrSvg !!}
            </div>

            <div class="text-center space-y-1">
                <p class="text-sm font-medium text-gray-800 dark:text-gray-200">
                    Scan this QR code with your iPhone camera
                </p>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Your iPhone will open a scanner page. Point it at the product barcode — the UPC will appear here automatically.
                </p>
            </div>

            <div class="flex items-center gap-2 text-xs text-gray-400 dark:text-gray-500">
                <svg class="animate-spin h-3 w-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                Waiting for scan…
            </div>

            <p class="text-xs text-gray-300 dark:text-gray-600 font-mono break-all max-w-xs">{{ $scanUrl }}</p>
        </div>
    </div>
</div>
