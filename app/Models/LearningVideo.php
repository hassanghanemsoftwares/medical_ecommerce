<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Translatable\HasTranslations;

class LearningVideo extends Model
{
    use HasFactory, LogsActivity, HasTranslations;

    protected $fillable = [
        'title',
        'description',
        'video',
    ];
    public $translatable = [
        'title',
        'description',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['title', 'description', 'video'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('LearningVideo');
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return strtolower(class_basename($this)) . '.' . $eventName;
    }
}
