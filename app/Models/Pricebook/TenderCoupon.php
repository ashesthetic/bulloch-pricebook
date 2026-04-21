<?php

namespace App\Models\Pricebook;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TenderCoupon extends Model
{
    protected $table = 'pb_tenders_coupons';
    protected $primaryKey = 'item_number';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'item_number',
        'english_description',
        'french_description',
        'prompt_for_amount',
        'tender_type',
        'amount',
        'loyalty_card_description',
        'loyalty_card_restriction',
        'loyalty_card_swipe_type',
        'type_of_restrictions',
        'restriction_identifier',
        'available_at_pump_only',
        'available_in_kiosk_only',
        'coupon_not_active',
        'max_per_customer',
        'start_date',
        'end_date',
        'coupon_accounting_implications',
    ];

    protected $casts = [
        'prompt_for_amount'      => 'boolean',
        'loyalty_card_restriction' => 'boolean',
        'available_at_pump_only' => 'boolean',
        'available_in_kiosk_only' => 'boolean',
        'coupon_not_active'      => 'boolean',
        'start_date'             => 'date',
        'end_date'               => 'date',
    ];

    public function upcs(): HasMany
    {
        return $this->hasMany(TenderCouponUpc::class, 'item_number', 'item_number');
    }
}
