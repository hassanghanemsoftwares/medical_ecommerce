<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class OrderDetail extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'order_id',
        'variant_id',
        'quantity',
        'price',
        'discount',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function variant()
    {
        return $this->belongsTo(Variant::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['order_id', 'variant_id', 'quantity', 'price', 'discount'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('order_detail');
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return strtolower(class_basename($this)) . '.' . $eventName;
    }

    public function getTotalAttribute()
    {
        $total = $this->price * $this->quantity;
        if ($this->discount) {
            $total -= ($this->discount / 100) * $total;
        }
        return $total;
    }
}
