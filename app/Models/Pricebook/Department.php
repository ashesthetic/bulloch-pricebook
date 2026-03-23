<?php

namespace App\Models\Pricebook;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $table = 'pb_departments';
    protected $primaryKey = 'department_number';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'department_number',
        'description',
        'shift_report_flag',
        'sales_summary_report',
        'owner',
        'bt9000_inventory_control',
        'conexxus_product_code',
        'gift_card_department',
    ];

    protected $casts = [
        'shift_report_flag' => 'boolean',
        'sales_summary_report' => 'boolean',
        'bt9000_inventory_control' => 'boolean',
        'gift_card_department' => 'boolean',
    ];

    public function skus(): HasMany
    {
        return $this->hasMany(Sku::class, 'department_number', 'department_number');
    }
}
