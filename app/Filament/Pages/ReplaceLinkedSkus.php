<?php

namespace App\Filament\Pages;

use App\Models\Pricebook\Sku;
use App\Models\Pricebook\SkuLinkedSku;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class ReplaceLinkedSkus extends Page
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

        return $user->hasPermissionTo('edit_skus');
    }

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $navigationGroup = 'Pricebook — Inventory';

    protected static ?string $navigationLabel = 'Replace Linked SKUs';

    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.pages.replace-linked-skus';

    /** @var array<int, array{old_item_number: string, old_description: string, usage_count: int, new_item_number: string}> */
    public array $rows = [];

    public function mount(): void
    {
        $this->refreshRows();
    }

    public function refreshRows(): void
    {
        $counts = DB::table('pb_sku_linked_skus')
            ->select('linked_item_number', DB::raw('count(*) as usage_count'))
            ->groupBy('linked_item_number')
            ->orderByDesc('usage_count')
            ->get();

        $descriptions = Sku::whereIn('item_number', $counts->pluck('linked_item_number'))
            ->pluck('english_description', 'item_number');

        $this->rows = $counts
            ->map(fn ($row) => [
                'old_item_number' => $row->linked_item_number,
                'old_description' => trim($descriptions->get($row->linked_item_number) ?? ''),
                'usage_count' => $row->usage_count,
                'new_item_number' => '',
            ])
            ->values()
            ->all();
    }

    public function applyReplacement(int $index): void
    {
        $this->applyRows([$index]);
    }

    public function applyAll(): void
    {
        $indexes = collect($this->rows)
            ->keys()
            ->filter(fn ($i) => filled($this->rows[$i]['new_item_number'] ?? null))
            ->all();

        if (empty($indexes)) {
            Notification::make()
                ->title('Nothing to apply')
                ->body('Enter a new item number for at least one row first.')
                ->warning()
                ->send();

            return;
        }

        $this->applyRows($indexes);
    }

    private function applyRows(array $indexes): void
    {
        $valuesReplaced = 0;
        $referencesUpdated = 0;
        $errors = [];

        DB::transaction(function () use ($indexes, &$valuesReplaced, &$referencesUpdated, &$errors): void {
            foreach ($indexes as $index) {
                $row = $this->rows[$index] ?? null;
                if ($row === null) {
                    continue;
                }

                $rawInput = trim($row['new_item_number'] ?? '');

                if ($rawInput === '') {
                    $errors[] = "{$row['old_item_number']}: enter a new item number.";

                    continue;
                }

                if (! ctype_digit($rawInput)) {
                    $errors[] = "{$row['old_item_number']}: \"{$rawInput}\" is not a valid item number.";

                    continue;
                }

                // Item numbers aren't all 13-digit zero-padded — try the value as typed
                // first, and only fall back to zero-padding it as a convenience.
                $newItemNumber = $rawInput;

                if (! Sku::whereKey($newItemNumber)->exists()) {
                    $padded = str_pad($rawInput, 13, '0', STR_PAD_LEFT);

                    if ($padded !== $newItemNumber && Sku::whereKey($padded)->exists()) {
                        $newItemNumber = $padded;
                    } else {
                        $errors[] = "{$row['old_item_number']}: item {$rawInput} does not exist.";

                        continue;
                    }
                }

                if ($newItemNumber === $row['old_item_number']) {
                    continue;
                }

                $updated = SkuLinkedSku::where('linked_item_number', $row['old_item_number'])
                    ->update(['linked_item_number' => $newItemNumber]);

                if ($updated > 0) {
                    $valuesReplaced++;
                    $referencesUpdated += $updated;
                }
            }
        });

        $this->refreshRows();

        if (! empty($errors)) {
            Notification::make()
                ->title('Some rows were skipped')
                ->body(implode(' ', $errors))
                ->warning()
                ->send();
        }

        if ($valuesReplaced > 0) {
            Notification::make()
                ->title('Linked SKUs replaced')
                ->body("Replaced {$valuesReplaced} linked SKU value(s) across {$referencesUpdated} reference(s).")
                ->success()
                ->send();
        }
    }
}
