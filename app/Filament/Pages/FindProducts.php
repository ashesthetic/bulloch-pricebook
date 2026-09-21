<?php

namespace App\Filament\Pages;

use App\Models\ModifierQueueItem;
use App\Models\Pricebook\Sku;
use App\Models\Pricebook\SkuUpc;
use App\Models\PrintQueueItem;
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

    public string $searchedUpc = '';

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

        // Clear the input so the next scan starts from a blank field, and force
        // the browser to refocus/clear it even though it's still focused.
        $this->copySourceUpc = '';
        $this->dispatch('copy-source-upc-cleared');

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
            'new_upc' => $this->searchedUpc,
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

    public function addToPrintQueue(): void
    {
        if ($this->product === null) {
            return;
        }

        PrintQueueItem::firstOrCreate(
            ['user_id' => auth()->id(), 'item_number' => $this->product['item_number']],
            ['copies' => 1]
        );

        Notification::make()
            ->title('Added to print queue')
            ->success()
            ->send();
    }

    public function addToModifierQueue(): void
    {
        if ($this->product === null) {
            return;
        }

        ModifierQueueItem::firstOrCreate([
            'user_id' => auth()->id(),
            'item_number' => $this->product['item_number'],
        ]);

        Notification::make()
            ->title('Added to modifier queue')
            ->success()
            ->send();
    }

    private function performLookup(string $rawUpc): void
    {
        $this->product = null;
        $this->newProductName = '';
        $this->notFound = false;

        $upc = trim($rawUpc);
        $this->searchedUpc = $upc;

        // Clear the input so the next scan starts from a blank field, and force
        // the browser to refocus/clear it even though it's still focused (Livewire
        // won't overwrite a focused input's value on its own).
        $this->upc = '';
        $this->dispatch('upc-cleared');

        if (blank($upc)) {
            return;
        }

        // Drop check digit (last digit), then left-pad to 13 digits.
        $upc = UpcBarcode::normalizeStoredPayload($upc, stripCheckDigit: true);

        $skuUpc = $upc === null
            ? null
            : SkuUpc::with(['sku' => fn ($query) => $query->withCount('upcs')->with(['department', 'priceGroup.quantityPricing', 'linkedSkus'])])->where('upc', $upc)->first();

        if ($skuUpc === null || $skuUpc->sku === null) {
            $this->notFound = true;

            return;
        }

        $productName = trim($skuUpc->sku->english_description);

        $linkedItemNumbers = $skuUpc->sku->linkedSkus->pluck('linked_item_number');

        $linkedSkuDetails = $linkedItemNumbers->isEmpty()
            ? collect()
            : Sku::whereIn('item_number', $linkedItemNumbers)->get()->keyBy('item_number');

        $linkedItems = $skuUpc->sku->linkedSkus
            ->map(function ($linkedSku) use ($linkedSkuDetails) {
                $detail = $linkedSkuDetails->get($linkedSku->linked_item_number);

                return [
                    'item_number' => $linkedSku->linked_item_number,
                    'mandatory' => $linkedSku->mandatory,
                    'english_description' => $detail ? trim($detail->english_description) : null,
                    'price' => $detail?->price,
                ];
            })
            ->toArray();

        $this->product = [
            'item_number' => $skuUpc->sku->item_number,
            'english_description' => $productName,
            'price' => $skuUpc->sku->price,
            'upc' => $skuUpc->upc,
            'upc_count' => $skuUpc->sku->upcs_count,
            'has_multiple_upcs' => $skuUpc->sku->upcs_count > 1,
            'department_number' => $skuUpc->sku->department_number,
            'department_description' => $skuUpc->sku->department?->description,
            'price_group_number' => $skuUpc->sku->price_group_number,
            'price_group_description' => $skuUpc->sku->priceGroup?->english_description,
            'price_group_price' => $skuUpc->sku->priceGroup?->price,
            'price_group_quantity_pricing' => $skuUpc->sku->priceGroup
                ? $skuUpc->sku->priceGroup->quantityPricing
                    ->map(fn ($qp) => ['quantity' => $qp->quantity, 'price' => $qp->price])
                    ->toArray()
                : [],
            'linked_items' => $linkedItems,
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

            $this->performLookup($this->searchedUpc);

            return;
        }

        Notification::make()
            ->title('Product created')
            ->body("Created item #{$newSku->item_number} with UPC {$this->product['upc']}.")
            ->success()
            ->send();

        $this->performLookup($this->searchedUpc);
    }
}
