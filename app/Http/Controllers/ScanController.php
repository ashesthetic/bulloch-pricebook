<?php

namespace App\Http\Controllers;

use App\Filament\Resources\SkuResource;
use App\Models\Pricebook\SkuUpc;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ScanController extends Controller
{
    public function show(string $token)
    {
        if (! Cache::has("scan:{$token}")) {
            abort(404, 'Scan session expired or invalid.');
        }

        return view('scan.index', ['token' => $token]);
    }

    public function store(Request $request, string $token): JsonResponse
    {
        if (! Cache::has("scan:{$token}")) {
            return response()->json(['error' => 'Session expired'], 410);
        }

        $upc = $request->input('upc', '');
        Cache::put("scan:{$token}", $upc, now()->addMinutes(5));

        $normalized = str_pad(substr(trim($upc), 0, -1), 13, '0', STR_PAD_LEFT);
        $skuUpc = SkuUpc::where('upc', $normalized)->with('sku')->first();

        $editUrl = $skuUpc?->sku
            ? SkuResource::getUrl('edit', ['record' => $skuUpc->item_number])
            : null;

        return response()->json([
            'success'      => true,
            'edit_url'     => $editUrl,
            'product_name' => $skuUpc?->sku?->english_description,
        ]);
    }
}
