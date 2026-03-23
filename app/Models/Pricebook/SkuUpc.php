<?php

namespace App\Models\Pricebook;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SkuUpc extends Model
{
    protected $table = 'pb_sku_upcs';

    protected $fillable = [
        'item_number',
        'upc',
    ];

    public function sku(): BelongsTo
    {
        return $this->belongsTo(Sku::class, 'item_number', 'item_number');
    }
}
