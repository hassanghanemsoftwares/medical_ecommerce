<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Address extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'client_id',
        'country',
        'city',
        'district',
        'governorate',
        'specifications',
        'latitude',
        'longitude',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['client_id', 'country', 'city', 'district', 'governorate', 'specifications', 'is_active', 'is_default'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('address');
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return strtolower(class_basename($this)) . '.' . $eventName;
    }
}
