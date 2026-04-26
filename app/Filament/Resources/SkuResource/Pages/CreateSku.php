<?php

namespace App\Filament\Resources\SkuResource\Pages;

use App\Filament\Resources\SkuResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class CreateSku extends CreateRecord
{
    protected static string $resource = SkuResource::class;

    public ?string $activeScanToken = null;

    public ?string $activeScanUrl = null;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('scan-barcode')
                ->label('Scan Barcode')
                ->icon('heroicon-o-camera')
                ->color('gray')
                ->mountUsing(fn () => $this->generateScanToken())
                ->modalContent(fn () => view('filament.scan.qr-modal', [
                    'qrSvg' => QrCode::size(200)->generate($this->activeScanUrl),
                    'scanUrl' => $this->activeScanUrl,
                ]))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Cancel')
                ->action(fn () => null),
        ];
    }

    public function generateScanToken(): void
    {
        $token = (string) Str::uuid();
        Cache::put("scan:{$token}", '', now()->addMinutes(5));
        $this->activeScanToken = $token;
        $this->activeScanUrl = rtrim(config('app.scan_url'), '/') . '/scan/' . $token;
        $this->dispatch('scan-ready');
    }

    public function checkScanToken(): void
    {
        if (! $this->activeScanToken) {
            return;
        }

        $upc = Cache::get("scan:{$this->activeScanToken}");

        if ($upc === null || $upc === '') {
            return;
        }

        $this->addUpc($upc);

        Cache::forget("scan:{$this->activeScanToken}");
        $this->activeScanToken = null;

        $this->dispatch('close-modal', id: 'scan-barcode');
    }

    #[On('barcode-detected')]
    public function handleBarcodeDetected(string $upc): void
    {
        if (! $this->activeScanToken) {
            return;
        }

        if ($this->addUpc($upc)) {
            $this->activeScanToken = null;
            $this->dispatch('close-modal', id: 'scan-barcode');
        }
    }

    private function addUpc(string $rawUpc): bool
    {
        $normalized = str_pad(substr(trim($rawUpc), 0, -1), 13, '0', STR_PAD_LEFT);

        $upcs = $this->data['upcs'] ?? [];

        foreach ($upcs as $entry) {
            if (($entry['upc'] ?? '') === $normalized) {
                Notification::make()
                    ->title('UPC already added')
                    ->body("{$normalized} is already in the UPC list.")
                    ->warning()
                    ->send();

                return false;
            }
        }

        $upcs[(string) Str::uuid()] = ['upc' => $normalized];
        $this->data = array_merge($this->data, ['upcs' => $upcs]);

        return true;
    }
}
