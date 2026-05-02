@php
    use App\Support\UpcBarcode;

    $storedUpc = UpcBarcode::normalizeStoredPayload($upc ?? null);
    $barcodeValue = UpcBarcode::withCheckDigit($storedUpc);
@endphp

@if ($storedUpc !== null && $barcodeValue !== null)
    <style>
        .sku-upc-barcode-preview {
            box-sizing: border-box;
            width: 100%;
        }

        @media (min-width: 768px) {
            .sku-upc-barcode-preview {
                width: 50%;
            }
        }

        @media (min-width: 1024px) {
            .sku-upc-barcode-preview {
                width: 25%;
            }
        }
    </style>

    <div class="sku-upc-barcode-preview space-y-1 rounded-md border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-900">
        <div class="font-mono text-xs text-gray-700 dark:text-gray-300">{{ $storedUpc }}</div>
        <div class="w-40 max-w-full">
            {!! UpcBarcode::itf14Svg($storedUpc, 48) !!}
        </div>
        <div class="font-mono text-[10px] text-gray-500 dark:text-gray-400">{{ $barcodeValue }}</div>
    </div>
@endif
