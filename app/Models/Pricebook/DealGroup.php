<?php

namespace App\Models\Pricebook;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DealGroup extends Model
{
    protected $table = 'pb_deal_groups';

    protected $fillable = [
        'deal_group_number',
        'type',
        'english_description',
        'french_description',
        'start_date',
        'end_date',
        'fuel_mix_and_match_check',
        'dont_calculate_deal',
        'deal_not_active',
        'available_in_kiosk_only',
        'cpl_stacking_cpn',
        'available_at_pump_only',
        'reason_code_for_deal',
        'station_id_for_deal',
        'fixed_dollar_off',
        'max_per_customer',
        'req_fuel_pos_grade',
        'req_fuel_litres',
        'loyalty_card_description',
        'loyalty_card_restriction',
        'loyalty_card_swipe_type',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'fuel_mix_and_match_check' => 'boolean',
        'dont_calculate_deal' => 'boolean',
        'deal_not_active' => 'boolean',
        'available_in_kiosk_only' => 'boolean',
        'cpl_stacking_cpn' => 'boolean',
        'available_at_pump_only' => 'boolean',
        'loyalty_card_restriction' => 'boolean',
        'fixed_dollar_off' => 'decimal:2',
    ];

    public function components(): HasMany
    {
        return $this->hasMany(DealGroupComponent::class, 'deal_group_id');
    }

    public function cplFuelDiscounts(): HasMany
    {
        return $this->hasMany(DealGroupCplFuelDiscount::class, 'deal_group_id');
    }
}
