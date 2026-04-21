<?php

namespace App\Models\Pricebook;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenderCouponUpc extends Model
{
    protected $table = 'pb_tender_coupon_upcs';

    protected $fillable = [
        'item_number',
        'upc',
    ];

    public function tenderCoupon(): BelongsTo
    {
        return $this->belongsTo(TenderCoupon::class, 'item_number', 'item_number');
    }
}
