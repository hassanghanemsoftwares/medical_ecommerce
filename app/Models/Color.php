<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Translatable\HasTranslations;

class Color extends Model
{
    use HasFactory, LogsActivity, HasTranslations;
    public $translatable = ['name'];

    protected $fillable = [
        'name',
        'code',
        'color_season_id',
    ];

    public function colorSeason()
    {
        return $this->belongsTo(ColorSeason::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'code', 'color_season_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('color');
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return strtolower(class_basename($this)) . '.' . $eventName;
    }
}
