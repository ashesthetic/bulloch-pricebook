<?php

namespace App\Models\Pricebook;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoyaltyCardBin extends Model
{
    protected $table = 'pb_loyalty_card_bins';

    protected $fillable = [
        'loyalty_card_id',
        'start_iso_bin',
        'end_iso_bin',
        'min_length',
        'max_length',
        'check_digit',
    ];

    public function loyaltyCard(): BelongsTo
    {
        return $this->belongsTo(LoyaltyCard::class, 'loyalty_card_id');
    }
}
