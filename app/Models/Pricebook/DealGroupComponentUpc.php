<?php

namespace App\Models\Pricebook;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DealGroupComponentUpc extends Model
{
    protected $table = 'pb_deal_group_component_upcs';

    protected $fillable = [
        'deal_group_component_id',
        'upc',
    ];

    public function component(): BelongsTo
    {
        return $this->belongsTo(DealGroupComponent::class, 'deal_group_component_id');
    }
}
