<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ReturnOrderDetail extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'return_order_id',
        'variant_id',
        'quantity',
        'price',
        'refund_amount',
    ];

    public function returnOrder()
    {
        return $this->belongsTo(ReturnOrder::class);
    }

    public function variant()
    {
        return $this->belongsTo(Variant::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['return_order_id', 'variant_id', 'quantity', 'price', 'refund_amount'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('return_order_detail');
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return strtolower(class_basename($this)) . '.' . $eventName;
    }

    public function getTotalAttribute()
    {
        return $this->price * $this->quantity;
    }
}
