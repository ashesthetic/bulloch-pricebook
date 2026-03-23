<?php

namespace App\Models\Pricebook;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceGroupQuantityPricing extends Model
{
    protected $table = 'pb_price_group_quantity_pricing';

    protected $fillable = [
        'price_group_number',
        'quantity',
        'price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function priceGroup(): BelongsTo
    {
        return $this->belongsTo(PriceGroup::class, 'price_group_number', 'price_group_number');
    }
}
