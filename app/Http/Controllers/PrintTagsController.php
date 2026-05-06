<?php

namespace App\Http\Controllers;

use App\Models\PrintQueueItem;
use App\Support\UpcBarcode;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PrintTagsController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $ids = array_filter(explode(',', $request->query('ids', '')));

        $items = PrintQueueItem::whereIn('id', $ids)
            ->where('user_id', auth()->id())
            ->with('sku.upcs')
            ->get();

        $tags = [];

        foreach ($items as $item) {
            $sku = $item->sku;
            if (! $sku) {
                continue;
            }

            $upc = $sku->upcs->first()?->upc;
            $barcodeSvg = $upc ? UpcBarcode::ean13Svg($upc, height: 44) : '';

            // Strip the inline style from the SVG root element — it contains width:100% which
            // causes DomPDF's SVG renderer to expand the image to full page width.
            if ($barcodeSvg) {
                $barcodeSvg = preg_replace('/<svg([^>]*)\sstyle="[^"]*"/', '<svg$1', $barcodeSvg);
            }

            $tag = [
                'name' => trim($sku->english_description),
                'price' => number_format($sku->price, 2),
                'upc' => $upc ? UpcBarcode::ean13($upc) : '',
                'barcode_b64' => $barcodeSvg ? base64_encode($barcodeSvg) : '',
            ];

            for ($i = 0; $i < max(1, $item->copies); $i++) {
                $tags[] = $tag;
            }
        }

        // Pad to fill the last row of 4
        $remainder = count($tags) % 4;
        if ($remainder !== 0) {
            for ($i = 0; $i < 4 - $remainder; $i++) {
                $tags[] = null;
            }
        }

        if ($request->boolean('preview')) {
            return response(view('print.price-tags', compact('tags')));
        }

        $pdf = Pdf::loadView('print.price-tags', compact('tags'))
            ->setPaper('letter', 'portrait');

        return $pdf->download('price-tags.pdf');
    }
}
