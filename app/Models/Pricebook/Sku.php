<?php

namespace App\Models\Pricebook;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sku extends Model
{
    protected $table = 'pb_skus';
    protected $primaryKey = 'item_number';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'item_number',
        'english_description',
        'french_description',
        'price',
        'department_number',
        'price_group_number',
        'item_deposit',
        'promo_code',
        'host_product_code',
        'tax1', 'tax2', 'tax3', 'tax4', 'tax5', 'tax6', 'tax7', 'tax8',
        'prompt_for_price',
        'item_not_active',
        'tax_included_price',
        'wash_type',
        'car_wash_controller_code',
        'upsell_qty_car_wash',
        'petro_canada_pass_code',
        'item_desc_not_on_2nd_monitor',
        'ontario_rst_tax_off',
        'ontario_rst_tax_on',
        'federal_baked_good_item',
        'prevent_bt9000_inventory_control',
        'conexxus_product_code',
        'car_wash_expiry_in_days',
        'afd_car_wash_position',
        'age_requirements',
        'redemption_only',
        'loyalty_card_eligible',
        'delivery_channel_price',
        'tax_strategy_id_from_nacs',
        'owner',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'item_deposit' => 'decimal:2',
        'delivery_channel_price' => 'decimal:2',
        'tax1' => 'boolean',
        'tax2' => 'boolean',
        'tax3' => 'boolean',
        'tax4' => 'boolean',
        'tax5' => 'boolean',
        'tax6' => 'boolean',
        'tax7' => 'boolean',
        'tax8' => 'boolean',
        'prompt_for_price' => 'boolean',
        'item_not_active' => 'boolean',
        'tax_included_price' => 'boolean',
        'item_desc_not_on_2nd_monitor' => 'boolean',
        'ontario_rst_tax_off' => 'boolean',
        'ontario_rst_tax_on' => 'boolean',
        'federal_baked_good_item' => 'boolean',
        'prevent_bt9000_inventory_control' => 'boolean',
        'redemption_only' => 'boolean',
        'loyalty_card_eligible' => 'boolean',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_number', 'department_number');
    }

    public function priceGroup(): BelongsTo
    {
        return $this->belongsTo(PriceGroup::class, 'price_group_number', 'price_group_number');
    }

    public function upcs(): HasMany
    {
        return $this->hasMany(SkuUpc::class, 'item_number', 'item_number');
    }

    public function quantityPricing(): HasMany
    {
        return $this->hasMany(SkuQuantityPricing::class, 'item_number', 'item_number');
    }

    public function linkedSkus(): HasMany
    {
        return $this->hasMany(SkuLinkedSku::class, 'item_number', 'item_number');
    }

    public function linkableSkus(): HasMany
    {
        return $this->hasMany(SkuLinkableSku::class, 'item_number', 'item_number');
    }
}
