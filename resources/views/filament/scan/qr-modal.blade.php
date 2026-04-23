<div
    x-data
    x-init="
        let t = setInterval(() => $wire.checkScanToken(), 1500);
        $wire.$watch('activeScanToken', v => { if (!v) clearInterval(t); });
    "
>
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
