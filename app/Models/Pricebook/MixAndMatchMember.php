<?php

namespace App\Models\Pricebook;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MixAndMatchMember extends Model
{
    protected $table = 'pb_mix_and_match_members';

    protected $fillable = [
        'mix_and_match_identifier',
        'item_number',
    ];

    public function mixAndMatch(): BelongsTo
    {
        return $this->belongsTo(MixAndMatch::class, 'mix_and_match_identifier', 'mix_and_match_identifier');
    }
}
