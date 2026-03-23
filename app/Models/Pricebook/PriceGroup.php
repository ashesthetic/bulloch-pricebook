<?php

namespace App\Models\Pricebook;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PriceGroup extends Model
{
    protected $table = 'pb_price_groups';
    protected $primaryKey = 'price_group_number';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'price_group_number',
        'english_description',
        'french_description',
        'price',
    ];

    protected $casts = [
        'price' => 'decimal:2',
    ];

    public function quantityPricing(): HasMany
    {
        return $this->hasMany(PriceGroupQuantityPricing::class, 'price_group_number', 'price_group_number');
    }

    public function skus(): HasMany
    {
        return $this->hasMany(Sku::class, 'price_group_number', 'price_group_number');
    }
}
