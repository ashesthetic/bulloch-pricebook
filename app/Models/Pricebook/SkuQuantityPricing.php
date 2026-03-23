<?php

namespace App\Models\Pricebook;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SkuQuantityPricing extends Model
{
    protected $table = 'pb_sku_quantity_pricing';

    protected $fillable = [
        'item_number',
        'quantity',
        'price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function sku(): BelongsTo
    {
        return $this->belongsTo(Sku::class, 'item_number', 'item_number');
    }
}
