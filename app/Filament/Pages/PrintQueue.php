<?php

namespace App\Filament\Pages;

use App\Models\PrintQueueItem;
use App\Models\Pricebook\SkuUpc;
use App\Support\UpcBarcode;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;

class PrintQueue extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-printer';

    protected static ?string $navigationGroup = 'Pricebook — Inventory';

    protected static ?string $navigationLabel = 'Print Queue';

    protected static ?int $navigationSort = 2;

    protected static string $view = 'filament.pages.print-queue';

    public string $upc = '';

    public ?string $lastScanned = null;

    public bool $scanNotFound = false;

    /** @var array<int> */
    public array $selectedItems = [];

    public Collection $queueItems;

    public function mount(): void
    {
        $this->queueItems = collect();
        $this->refreshQueue();
    }

    public function refreshQueue(): void
    {
        $this->queueItems = PrintQueueItem::where('user_id', auth()->id())
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

        PrintQueueItem::firstOrCreate(
            ['user_id' => auth()->id(), 'item_number' => $skuUpc->item_number],
            ['copies' => 1],
        );

        $this->lastScanned = trim($skuUpc->sku->english_description);
        $this->upc = '';

        $this->refreshQueue();

        Notification::make()
            ->title('Added to queue')
            ->body($this->lastScanned)
            ->success()
            ->send();
    }

    public function removeItem(int $id): void
    {
        PrintQueueItem::where('id', $id)
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
        PrintQueueItem::where('user_id', auth()->id())->delete();
        $this->selectedItems = [];
        $this->refreshQueue();
    }

    public function updateCopies(int $id, int $copies): void
    {
        PrintQueueItem::where('id', $id)
            ->where('user_id', auth()->id())
            ->update(['copies' => max(1, min(99, $copies))]);

        $this->refreshQueue();
    }

    public function exportPdf(): void
    {
        if (empty($this->selectedItems)) {
            return;
        }

        $this->dispatch('open-pdf-export', ids: $this->selectedItems);
    }

    public function previewPdf(): void
    {
        if (empty($this->selectedItems)) {
            return;
        }

        $this->dispatch('open-pdf-preview', ids: $this->selectedItems);
    }
}
