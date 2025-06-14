<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Translatable\HasTranslations;

class SubscriptionPlan extends Model
{
    use HasFactory, LogsActivity,HasTranslations;

    protected $fillable = [
        'name',
        'price',
        'duration_in_days',
        'is_active',
    ];
    
    public $translatable = [
        'name',
    ];

    protected $casts = [
        'name' => 'array',
        'is_active' => 'boolean',
    ];

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name',
                'price',
                'duration_in_days',
                'is_active',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('SubscriptionPlan');
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return strtolower(class_basename($this)) . '.' . $eventName;
    }
}
