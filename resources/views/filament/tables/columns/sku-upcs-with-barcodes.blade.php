@php
    use App\Support\UpcBarcode;

    $upcs = $getRecord()->upcs;
@endphp

<div class="space-y-3">
    @forelse ($upcs as $upc)
        @php
            $storedUpc = UpcBarcode::normalizeStoredPayload($upc->upc);
            $barcodeValue = UpcBarcode::withCheckDigit($storedUpc);
        @endphp

        @if ($storedUpc !== null && $barcodeValue !== null)
            <div class="space-y-1" title="SKU {{ $getRecord()->item_number }} UPC {{ $storedUpc }} barcode {{ $barcodeValue }}">
                <div class="font-mono text-xs text-gray-700 dark:text-gray-300">{{ $storedUpc }}</div>
                <div class="w-36 max-w-full">
                    {!! UpcBarcode::itf14Svg($storedUpc, 42) !!}
                </div>
                <div class="font-mono text-[10px] text-gray-500 dark:text-gray-400">{{ $barcodeValue }}</div>
            </div>
        @endif
    @empty
        <span class="text-gray-400 dark:text-gray-500">—</span>
    @endforelse
</div>
