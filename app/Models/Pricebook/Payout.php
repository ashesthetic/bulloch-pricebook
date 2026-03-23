<?php

namespace App\Models\Pricebook;

use Illuminate\Database\Eloquent\Model;

class Payout extends Model
{
    protected $table = 'pb_payouts';
    protected $primaryKey = 'payout_number';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'payout_number',
        'english_description',
        'french_description',
    ];
}
