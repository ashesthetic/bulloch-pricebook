<?php

namespace App\Models\Pricebook;

use Illuminate\Database\Eloquent\Model;

class SkuLinkableSku extends Model
{
    protected $table = 'pb_sku_linkable_skus';

    protected $fillable = [
        'item_number',
        'linkable_item_number',
    ];
}
