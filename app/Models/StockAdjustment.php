<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class StockAdjustment extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'variant_id',
        'warehouse_id',
        'shelf_id',
        'adjustment_type',
        'quantity',
        'cost_per_item',
        'reason',
        'adjusted_by',
        'reference_type',
        'reference_id',
    ];

    public function variant()
    {
        return $this->belongsTo(Variant::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function shelf()
    {
        return $this->belongsTo(Shelf::class);
    }

    public function adjustedBy()
    {
        return $this->belongsTo(User::class, 'adjusted_by');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['variant_id', 'warehouse_id', 'shelf_id', 'adjustment_type', 'quantity', 'cost_per_item', 'reason'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('stock_adjustment');
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return strtolower(class_basename($this)) . '.' . $eventName;
    }
}
