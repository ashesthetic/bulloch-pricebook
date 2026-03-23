<?php

namespace App\Models\Pricebook;

use Illuminate\Database\Eloquent\Model;

class SkuLinkedSku extends Model
{
    protected $table = 'pb_sku_linked_skus';

    protected $fillable = [
        'item_number',
        'linked_item_number',
        'mandatory',
    ];

    protected $casts = [
        'mandatory' => 'boolean',
    ];
}
