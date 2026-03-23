<?php

namespace App\Models\Pricebook;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MixAndMatch extends Model
{
    protected $table = 'pb_mix_and_matches';
    protected $primaryKey = 'mix_and_match_identifier';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'mix_and_match_identifier',
        'english_description',
        'french_description',
    ];

    public function members(): HasMany
    {
        return $this->hasMany(MixAndMatchMember::class, 'mix_and_match_identifier', 'mix_and_match_identifier');
    }
}
