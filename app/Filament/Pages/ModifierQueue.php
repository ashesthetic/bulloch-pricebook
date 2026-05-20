<?php

namespace App\Filament\Pages;

use App\Models\ModifierQueueItem;
use App\Models\Pricebook\Department;
use App\Models\Pricebook\PriceGroup;
use App\Models\Pricebook\Sku;
use App\Models\Pricebook\SkuUpc;
use App\Support\UpcBarcode;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;

class ModifierQueue extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-pencil-square';

    protected static ?string $navigationGroup = 'Pricebook — Inventory';

    protected static ?string $navigationLabel = 'Modifier Queue';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.modifier-queue';

    public string $upc = '';

    public ?string $lastScanned = null;

    public bool $scanNotFound = false;

    /** @var array<int> */
    public array $selectedItems = [];

    public Collection $queueItems;

    /** @var array<string, string> */
    public array $departments = [];

    /** @var array<string, string> */
    public array $priceGroups = [];

    public function mount(): void
    {
        $this->queueItems = collect();
        $this->departments = Department::orderBy('description')->pluck('description', 'department_number')->toArray();
        $this->priceGroups = PriceGroup::orderBy('english_description')->pluck('english_description', 'price_group_number')->toArray();
        $this->refreshQueue();
    }

    public function refreshQueue(): void
    {
        $this->queueItems = ModifierQueueItem::where('user_id', auth()->id())
            ->with('sku.upcs')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function searchByUpc(): void
    {
        $this->addFromUpc($this->upc);
    }

    #[On('barcode-detected')]
    public function handleBarcodeDetected(string $upc): void
    {
        $this->upc = $upc;
        $this->addFromUpc($upc);
    }

    private function addFromUpc(string $rawUpc): void
    {
        $this->lastScanned = null;
        $this->scanNotFound = false;

        $upc = trim($rawUpc);

        if (blank($upc)) {
            return;
        }

        $normalized = UpcBarcode::normalizeStoredPayload($upc, stripCheckDigit: true);

        $skuUpc = $normalized === null
            ? null
            : SkuUpc::with('sku')->where('upc', $normalized)->first();

        if ($skuUpc === null || $skuUpc->sku === null) {
            $this->scanNotFound = true;

            return;
        }

        ModifierQueueItem::firstOrCreate([
            'user_id' => auth()->id(),
            'item_number' => $skuUpc->item_number,
        ]);

        $this->lastScanned = trim($skuUpc->sku->english_description);
        $this->upc = '';

        $this->refreshQueue();

        Notification::make()
            ->title('Added to modifier queue')
            ->body($this->lastScanned)
            ->success()
            ->send();
    }

    public function removeItem(int $id): void
    {
        ModifierQueueItem::where('id', $id)
            ->where('user_id', auth()->id())
            ->delete();

        $this->selectedItems = array_values(array_filter(
            $this->selectedItems,
            fn ($selectedId) => $selectedId !== $id,
        ));

        $this->refreshQueue();
    }

    public function clearQueue(): void
    {
        ModifierQueueItem::where('user_id', auth()->id())->delete();
        $this->selectedItems = [];
        $this->refreshQueue();
    }

    public function applyChangeDepartment(string $departmentNumber): void
    {
        if (empty($this->selectedItems) || blank($departmentNumber)) {
            return;
        }

        $itemNumbers = ModifierQueueItem::whereIn('id', $this->selectedItems)
            ->where('user_id', auth()->id())
            ->pluck('item_number');

        Sku::whereIn('item_number', $itemNumbers)
            ->update(['department_number' => $departmentNumber]);

        $this->refreshQueue();

        Notification::make()
            ->title('Department updated')
            ->body(count($this->selectedItems) . ' item(s) updated.')
            ->success()
            ->send();
    }

    public function applyChangePriceGroup(?string $priceGroupNumber): void
    {
        if (empty($this->selectedItems)) {
            return;
        }

        $itemNumbers = ModifierQueueItem::whereIn('id', $this->selectedItems)
            ->where('user_id', auth()->id())
            ->pluck('item_number');

        Sku::whereIn('item_number', $itemNumbers)
            ->update(['price_group_number' => blank($priceGroupNumber) ? null : $priceGroupNumber]);

        $this->refreshQueue();

        Notification::make()
            ->title('Price group updated')
            ->body(count($this->selectedItems) . ' item(s) updated.')
            ->success()
            ->send();
    }

    public function applyChangePrice(string $price): void
    {
        if (empty($this->selectedItems) || blank($price)) {
            return;
        }

        $parsed = (float) $price;

        if ($parsed < 0) {
            return;
        }

        $itemNumbers = ModifierQueueItem::whereIn('id', $this->selectedItems)
            ->where('user_id', auth()->id())
            ->pluck('item_number');

        Sku::whereIn('item_number', $itemNumbers)
            ->update(['price' => round($parsed, 2)]);

        $this->refreshQueue();

        Notification::make()
            ->title('Price updated')
            ->body(count($this->selectedItems) . ' item(s) updated.')
            ->success()
            ->send();
    }

    public function applyToggleActive(bool $active): void
    {
        if (empty($this->selectedItems)) {
            return;
        }

        $itemNumbers = ModifierQueueItem::whereIn('id', $this->selectedItems)
            ->where('user_id', auth()->id())
            ->pluck('item_number');

        // item_not_active: true = inactive, false = active
        Sku::whereIn('item_number', $itemNumbers)
            ->update(['item_not_active' => ! $active]);

        $this->refreshQueue();

        $label = $active ? 'activated' : 'deactivated';

        Notification::make()
            ->title("Items {$label}")
            ->body(count($this->selectedItems) . " item(s) {$label}.")
            ->success()
            ->send();
    }
}
