<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class SubscriptionPlan extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'price',
        'duration',
        'is_active',
    ];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function isMonthly(): bool
    {
        return $this->duration === 'monthly';
    }

    public function isYearly(): bool
    {
        return $this->duration === 'yearly';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'variant_id',
                'warehouse_id',
                'shelf_id',
                'type',
                'quantity',
                'cost_per_item',
                'reason',
                'adjusted_by',
                'reference_id',
                'reference_type',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('stock_adjustment');
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return strtolower(class_basename($this)) . '.' . $eventName;
    }
}
