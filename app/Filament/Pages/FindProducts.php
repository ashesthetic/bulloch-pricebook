<?php

namespace App\Filament\Pages;

use App\Models\Pricebook\Sku;
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

    public bool $copyMode = false;

    public string $copySourceUpc = '';

    public ?array $copySourceProduct = null;

    public bool $copySourceNotFound = false;

    public function searchByUpc(): void
    {
        $this->performLookup($this->upc);
    }

    #[On('barcode-detected')]
    public function handleBarcodeDetected(string $upc): void
    {
        if ($this->copyMode) {
            $this->copySourceUpc = $upc;
            $this->lookupCopySource($upc);
        } else {
            $this->upc = $upc;
            $this->performLookup($upc);
        }
    }

    public function startCopyMode(): void
    {
        $this->copyMode = true;
        $this->copySourceUpc = '';
        $this->copySourceProduct = null;
        $this->copySourceNotFound = false;
    }

    public function cancelCopyMode(): void
    {
        $this->copyMode = false;
        $this->copySourceUpc = '';
        $this->copySourceProduct = null;
        $this->copySourceNotFound = false;
    }

    public function searchCopySource(): void
    {
        $this->lookupCopySource($this->copySourceUpc);
    }

    private function lookupCopySource(string $rawUpc): void
    {
        $this->copySourceProduct = null;
        $this->copySourceNotFound = false;

        $upc = trim($rawUpc);

        if (blank($upc)) {
            return;
        }

        $normalized = UpcBarcode::normalizeStoredPayload($upc, stripCheckDigit: true);

        $skuUpc = $normalized === null
            ? null
            : SkuUpc::with('sku')->where('upc', $normalized)->first();

        if ($skuUpc === null || $skuUpc->sku === null) {
            $this->copySourceNotFound = true;

            return;
        }

        $this->copySourceProduct = [
            'item_number' => $skuUpc->sku->item_number,
            'english_description' => trim($skuUpc->sku->english_description),
            'price' => $skuUpc->sku->price,
        ];
    }

    public function navigateToCreateWithCopy(): void
    {
        if ($this->copySourceProduct === null) {
            return;
        }

        $sku = Sku::with(['quantityPricing', 'linkedSkus'])
            ->find($this->copySourceProduct['item_number']);

        if ($sku === null) {
            Notification::make()
                ->title('Product no longer exists')
                ->danger()
                ->send();

            return;
        }

        session()->flash('sku_copy_data', [
            'new_upc' => $this->upc,
            'fields' => $sku->only(array_diff($sku->getFillable(), ['item_number'])),
            'quantityPricing' => $sku->quantityPricing
                ->map(fn ($qp) => ['quantity' => $qp->quantity, 'price' => $qp->price])
                ->toArray(),
            'linkedSkus' => $sku->linkedSkus
                ->map(fn ($ls) => ['linked_item_number' => $ls->linked_item_number, 'mandatory' => $ls->mandatory])
                ->toArray(),
        ]);

        $this->redirect(route('filament.admin.resources.skus.create'));
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
