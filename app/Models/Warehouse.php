<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Warehouse extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'location',
    ];
    public function shelves()
    {
        return $this->hasMany(Shelf::class);
    }
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'location'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('warehouse');
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return strtolower(class_basename($this)) . '.' . $eventName;
    }
}
