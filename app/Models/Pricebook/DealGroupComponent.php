<?php

namespace App\Models\Pricebook;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DealGroupComponent extends Model
{
    protected $table = 'pb_deal_group_components';

    protected $fillable = [
        'deal_group_id',
        'item_number',
        'price_group_number',
        'mix_and_match_identifier',
        'quantity',
        'price_for_quantity_one',
        'percentage_off',
        'amount_off',
        'coupon_accounting_implications',
    ];

    protected $casts = [
        'price_for_quantity_one' => 'decimal:2',
        'amount_off' => 'decimal:2',
    ];

    public function getComponentTypeAttribute(): string
    {
        if ($this->item_number !== null) {
            return 'item';
        }
        if ($this->price_group_number !== null) {
            return 'price_group';
        }
        return 'mix_and_match';
    }

    public function dealGroup(): BelongsTo
    {
        return $this->belongsTo(DealGroup::class, 'deal_group_id');
    }

    public function upcs(): HasMany
    {
        return $this->hasMany(DealGroupComponentUpc::class, 'deal_group_component_id');
    }
}
