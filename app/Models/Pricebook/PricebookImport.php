<?php

namespace App\Models\Pricebook;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PricebookImport extends Model
{
    protected $table = 'pb_imports';

    protected $fillable = [
        'file_path',
        'bt9000_version',
        'generated_by',
        'station_id',
        'file_creation_date',
        'file_created_at',
        'records_imported',
        'total_records',
        'processed_records',
        'progress_percentage',
        'current_section',
        'started_at',
        'finished_at',
        'status',
        'error_message',
    ];

    protected $casts = [
        'records_imported' => 'array',
        'file_created_at' => 'datetime',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];
}
