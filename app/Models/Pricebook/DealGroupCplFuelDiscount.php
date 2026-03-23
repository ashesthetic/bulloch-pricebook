<?php

namespace App\Models\Pricebook;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DealGroupCplFuelDiscount extends Model
{
    protected $table = 'pb_deal_group_cpl_fuel_discounts';

    protected $fillable = [
        'deal_group_id',
        'pos_grade',
        'cpl_discount_on_fuel',
    ];

    protected $casts = [
        'cpl_discount_on_fuel' => 'decimal:1',
    ];

    public function dealGroup(): BelongsTo
    {
        return $this->belongsTo(DealGroup::class, 'deal_group_id');
    }
}
