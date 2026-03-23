<?php

namespace App\Models\Pricebook;

use Illuminate\Database\Eloquent\Model;

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
    ];
}
