<?php

namespace App\Filament\Pages;

use App\Models\Pricebook\SkuUpc;
use Filament\Pages\Page;
use Livewire\Attributes\On;

class FindProducts extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-magnifying-glass';
    protected static ?string $navigationGroup = 'Pricebook — Inventory';
    protected static ?string $navigationLabel = 'Find Products';
    protected static ?int    $navigationSort  = 1;
    protected static string  $view            = 'filament.pages.find-products';

    public string $upc = '';
    public ?array $product = null;
    public bool $notFound = false;

    public function searchByUpc(): void
    {
        $this->performLookup($this->upc);
    }

    #[On('barcode-detected')]
    public function handleBarcodeDetected(string $upc): void
    {
        $this->upc = $upc;
        $this->performLookup($upc);
    }

    private function performLookup(string $rawUpc): void
    {
        $this->product = null;
        $this->notFound = false;

        $upc = trim($rawUpc);

        if (blank($upc)) {
            return;
        }

        // Drop check digit (last digit), then left-pad to 13 digits
        $upc = substr($upc, 0, -1);
        $upc = str_pad($upc, 13, '0', STR_PAD_LEFT);

        $skuUpc = SkuUpc::with('sku')->where('upc', $upc)->first();

        if ($skuUpc === null || $skuUpc->sku === null) {
            $this->notFound = true;

            return;
        }

        $this->product = [
            'item_number'         => $skuUpc->sku->item_number,
            'english_description' => trim($skuUpc->sku->english_description),
            'price'               => $skuUpc->sku->price,
            'upc'                 => $skuUpc->upc,
        ];
    }
}
