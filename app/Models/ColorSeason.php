<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Translatable\HasTranslations;

class ColorSeason extends Model
{
    use HasFactory, LogsActivity, HasTranslations;
    public $translatable = ['name'];
    protected $fillable = [
        'name',
    ];
    
    public function colors()
    {
        return $this->hasMany(Color::class, 'color_season_id');
    }
    
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('ColorSeason');
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return strtolower(class_basename($this)) . '.' . $eventName;
    }
}
