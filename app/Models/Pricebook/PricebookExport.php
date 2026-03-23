<?php

namespace App\Models\Pricebook;

use Illuminate\Database\Eloquent\Model;

class PricebookExport extends Model
{
    protected $table = 'pb_exports';

    protected $fillable = [
        'file_path',
        'records_exported',
        'started_at',
        'finished_at',
        'status',
        'error_message',
    ];

    protected $casts = [
        'started_at'  => 'datetime',
        'finished_at' => 'datetime',
    ];
}
