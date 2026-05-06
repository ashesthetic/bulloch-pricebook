<?php

namespace App\Models;

use App\Models\Pricebook\Sku;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrintQueueItem extends Model
{
    protected $fillable = ['user_id', 'item_number', 'copies'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function sku(): BelongsTo
    {
        return $this->belongsTo(Sku::class, 'item_number', 'item_number');
    }
}
