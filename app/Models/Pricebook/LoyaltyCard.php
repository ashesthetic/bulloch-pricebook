<?php

namespace App\Models\Pricebook;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoyaltyCard extends Model
{
    protected $table = 'pb_loyalty_cards';

    protected $fillable = [
        'card_name',
    ];

    public function bins(): HasMany
    {
        return $this->hasMany(LoyaltyCardBin::class, 'loyalty_card_id');
    }
}
