<?php

namespace Tests\Feature;

use App\Models\Pricebook\DealGroupComponent;
use App\Models\Pricebook\DealGroupComponentUpc;
use App\Models\Pricebook\MixAndMatchMember;
use App\Models\Pricebook\Sku;
use App\Models\Pricebook\SkuLinkableSku;
use App\Models\Pricebook\SkuLinkedSku;
use App\Models\Pricebook\SkuQuantityPricing;
use App\Models\Pricebook\SkuUpc;
use App\Services\Pricebook\SkuFromSharedUpcCreator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SkuFromSharedUpcCreatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_new_sku_from_a_shared_upc_and_moves_only_that_upc(): void
    {
        $source = Sku::query()->create([
            'item_number' => '0000000000001',
            'english_description' => 'SOURCE PRODUCT',
            'french_description' => 'SOURCE FR',
            'price' => 12.34,
            'department_number' => '10',
            'price_group_number' => '20',
            'item_deposit' => 0.10,
            'tax1' => true,
            'tax2' => false,
            'prompt_for_price' => true,
            'loyalty_card_eligible' => true,
            'owner' => 'Owner',
        ]);

        Sku::query()->create([
            'item_number' => '0000000000003',
            'english_description' => 'OTHER',
            'price' => 1.00,
            'department_number' => '10',
        ]);

        SkuUpc::query()->create(['item_number' => $source->item_number, 'upc' => '0000000001111']);
        SkuUpc::query()->create(['item_number' => $source->item_number, 'upc' => '0000000002222']);

        SkuQuantityPricing::query()->create(['item_number' => $source->item_number, 'quantity' => 2, 'price' => 20.00]);
        SkuLinkedSku::query()->create(['item_number' => $source->item_number, 'linked_item_number' => '0000000009999', 'mandatory' => true]);
        SkuLinkableSku::query()->create(['item_number' => $source->item_number, 'linkable_item_number' => '0000000008888']);
        MixAndMatchMember::query()->create(['mix_and_match_identifier' => 'MIX1', 'item_number' => $source->item_number]);

        $component = DealGroupComponent::query()->create([
            'deal_group_id' => 5,
            'item_number' => $source->item_number,
            'quantity' => 1,
            'price_for_quantity_one' => 9.99,
        ]);
        DealGroupComponentUpc::query()->create(['deal_group_component_id' => $component->id, 'upc' => '0000000001111']);

        $newSku = app(SkuFromSharedUpcCreator::class)->create(
            $source->item_number,
            '0000000001111',
            'NEW PRODUCT',
        );

        $this->assertSame('0000000000004', $newSku->item_number);
        $this->assertSame('NEW PRODUCT', $newSku->english_description);
        $this->assertSame('SOURCE FR', $newSku->french_description);
        $this->assertSame('10', $newSku->department_number);
        $this->assertSame('20', $newSku->price_group_number);
        $this->assertTrue($newSku->tax1);
        $this->assertFalse($newSku->tax2);
        $this->assertTrue($newSku->prompt_for_price);
        $this->assertTrue($newSku->loyalty_card_eligible);

        $this->assertSame(['0000000002222'], SkuUpc::query()->where('item_number', $source->item_number)->pluck('upc')->all());
        $this->assertSame(['0000000001111'], SkuUpc::query()->where('item_number', $newSku->item_number)->pluck('upc')->all());

        $this->assertDatabaseHas('pb_sku_quantity_pricing', ['item_number' => $newSku->item_number, 'quantity' => 2, 'price' => 20.00]);
        $this->assertDatabaseHas('pb_sku_linked_skus', ['item_number' => $newSku->item_number, 'linked_item_number' => '0000000009999', 'mandatory' => true]);
        $this->assertDatabaseHas('pb_sku_linkable_skus', ['item_number' => $newSku->item_number, 'linkable_item_number' => '0000000008888']);
        $this->assertDatabaseHas('pb_mix_and_match_members', ['mix_and_match_identifier' => 'MIX1', 'item_number' => $newSku->item_number]);
        $this->assertDatabaseHas('pb_deal_group_components', ['deal_group_id' => 5, 'item_number' => $newSku->item_number]);

        $newComponent = DealGroupComponent::query()
            ->where('item_number', $newSku->item_number)
            ->firstOrFail();

        $this->assertDatabaseHas('pb_deal_group_component_upcs', [
            'deal_group_component_id' => $newComponent->id,
            'upc' => '0000000001111',
        ]);
    }
}
