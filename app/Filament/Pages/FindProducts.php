<?php

namespace App\Filament\Pages;

use App\Models\Pricebook\SkuUpc;
use App\Services\Pricebook\SkuFromSharedUpcCreator;
use App\Support\UpcBarcode;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Livewire\Attributes\On;

class FindProducts extends Page
{
    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        return $user->hasPermissionTo('view_find_products');
    }

    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';

    protected static ?string $navigationGroup = 'Pricebook — Inventory';

    protected static ?string $navigationLabel = 'Find Products';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.find-products';

    public string $upc = '';

    public ?array $product = null;

    public string $newProductName = '';

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
        $this->newProductName = '';
        $this->notFound = false;

        $upc = trim($rawUpc);

        if (blank($upc)) {
            return;
        }

        // Drop check digit (last digit), then left-pad to 13 digits.
        $upc = UpcBarcode::normalizeStoredPayload($upc, stripCheckDigit: true);

        $skuUpc = $upc === null
            ? null
            : SkuUpc::with(['sku' => fn ($query) => $query->withCount('upcs')])->where('upc', $upc)->first();

        if ($skuUpc === null || $skuUpc->sku === null) {
            $this->notFound = true;

            return;
        }

        $productName = trim($skuUpc->sku->english_description);

        $this->product = [
            'item_number' => $skuUpc->sku->item_number,
            'english_description' => $productName,
            'price' => $skuUpc->sku->price,
            'upc' => $skuUpc->upc,
            'upc_count' => $skuUpc->sku->upcs_count,
            'has_multiple_upcs' => $skuUpc->sku->upcs_count > 1,
        ];

        $this->newProductName = $productName;
    }

    public function createProductFromScannedUpc(SkuFromSharedUpcCreator $creator): void
    {
        if ($this->product === null || ! $this->product['has_multiple_upcs']) {
            return;
        }

        $this->newProductName = trim($this->newProductName);

        $validated = $this->validate([
            'newProductName' => ['required', 'string', 'max:18'],
        ]);

        try {
            $newSku = $creator->create(
                $this->product['item_number'],
                $this->product['upc'],
                $validated['newProductName'],
            );
        } catch (\Throwable $exception) {
            Notification::make()
                ->title('Could not create product')
                ->body($exception->getMessage())
                ->danger()
                ->send();

            $this->performLookup($this->upc);

            return;
        }

        Notification::make()
            ->title('Product created')
            ->body("Created item #{$newSku->item_number} with UPC {$this->product['upc']}.")
            ->success()
            ->send();

        $this->performLookup($this->upc);
    }
}
