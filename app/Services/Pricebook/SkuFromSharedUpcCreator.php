<?php

namespace App\Services\Pricebook;

use App\Models\Pricebook\DealGroupComponent;
use App\Models\Pricebook\DealGroupComponentUpc;
use App\Models\Pricebook\MixAndMatchMember;
use App\Models\Pricebook\Sku;
use App\Models\Pricebook\SkuLinkableSku;
use App\Models\Pricebook\SkuLinkedSku;
use App\Models\Pricebook\SkuQuantityPricing;
use App\Models\Pricebook\SkuUpc;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class SkuFromSharedUpcCreator
{
    public function create(string $sourceItemNumber, string $upc, string $englishDescription): Sku
    {
        return DB::transaction(function () use ($sourceItemNumber, $upc, $englishDescription): Sku {
            $sourceSku = Sku::query()
                ->withCount('upcs')
                ->whereKey($sourceItemNumber)
                ->lockForUpdate()
                ->firstOrFail();

            if ($sourceSku->upcs_count < 2) {
                throw new RuntimeException('This product no longer has multiple UPCs.');
            }

            $skuUpc = SkuUpc::query()
                ->where('item_number', $sourceSku->item_number)
                ->where('upc', $upc)
                ->lockForUpdate()
                ->first();

            if ($skuUpc === null) {
                throw new RuntimeException('The scanned UPC is no longer attached to this product.');
            }

            $newSku = $sourceSku->replicate(['item_number', 'upcs_count']);
            $newSku->item_number = $this->nextItemNumber();
            $newSku->english_description = $englishDescription;
            $newSku->save();

            $this->copySkuChildren($sourceSku->item_number, $newSku->item_number);
            $this->copyProductReferences($sourceSku->item_number, $newSku->item_number);

            $skuUpc->update(['item_number' => $newSku->item_number]);

            return $newSku;
        });
    }

    private function nextItemNumber(): string
    {
        $candidate = Sku::query()->count() + 1;

        do {
            $itemNumber = str_pad((string) $candidate, 13, '0', STR_PAD_LEFT);
            $candidate++;
        } while (Sku::query()->whereKey($itemNumber)->exists());

        return $itemNumber;
    }

    private function copySkuChildren(string $sourceItemNumber, string $newItemNumber): void
    {
        SkuQuantityPricing::query()
            ->where('item_number', $sourceItemNumber)
            ->get()
            ->each(function (SkuQuantityPricing $quantityPricing) use ($newItemNumber): void {
                $copy = $quantityPricing->replicate();
                $copy->item_number = $newItemNumber;
                $copy->save();
            });

        SkuLinkedSku::query()
            ->where('item_number', $sourceItemNumber)
            ->get()
            ->each(function (SkuLinkedSku $linkedSku) use ($newItemNumber): void {
                $copy = $linkedSku->replicate();
                $copy->item_number = $newItemNumber;
                $copy->mandatory = $linkedSku->mandatory;
                $copy->save();
            });

        SkuLinkableSku::query()
            ->where('item_number', $sourceItemNumber)
            ->get()
            ->each(function (SkuLinkableSku $linkableSku) use ($newItemNumber): void {
                $copy = $linkableSku->replicate();
                $copy->item_number = $newItemNumber;
                $copy->save();
            });
    }

    private function copyProductReferences(string $sourceItemNumber, string $newItemNumber): void
    {
        MixAndMatchMember::query()
            ->where('item_number', $sourceItemNumber)
            ->get()
            ->each(function (MixAndMatchMember $member) use ($newItemNumber): void {
                $copy = $member->replicate();
                $copy->item_number = $newItemNumber;
                $copy->save();
            });

        DealGroupComponent::query()
            ->where('item_number', $sourceItemNumber)
            ->get()
            ->each(function (DealGroupComponent $component) use ($newItemNumber): void {
                $copy = $component->replicate();
                $copy->item_number = $newItemNumber;
                $copy->save();

                DealGroupComponentUpc::query()
                    ->where('deal_group_component_id', $component->id)
                    ->get()
                    ->each(function (DealGroupComponentUpc $upc) use ($copy): void {
                        $upcCopy = $upc->replicate();
                        $upcCopy->deal_group_component_id = $copy->id;
                        $upcCopy->save();
                    });
            });
    }
}
