<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Casts\Attribute;

class HomeBanner extends Model
{
    use HasFactory, LogsActivity, HasTranslations;

    protected $fillable = [
        'home_section_id',
        'image',
        'image480w',
        'link',
        'title',
        'subtitle',
        'arrangement',
        'is_active'
    ];
    public $translatable = [
        'title',
        'subtitle',
    ];
    protected $casts = [
        'arrangement' => 'integer',
    ];

    public function homeSection()
    {
        return $this->belongsTo(HomeSection::class);
    }
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'home_section_id',
                'image',
                'image480w',
                'link',
                'title',
                'subtitle',
                'arrangement',
                'is_active'
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('HomeBanner');
    }
    protected function image(): Attribute
    {
        return new Attribute(
            get: function () {
                return asset(Storage::url($this->attributes['image']));
            }
        );
    }
    protected function image480w(): Attribute
    {
        return new Attribute(
            get: function () {
                return asset(Storage::url($this->attributes['image480w']));
            }
        );
    }
    public function getDescriptionForEvent(string $eventName): string
    {
        return strtolower(class_basename($this)) . '.' . $eventName;
    }

    public static function storeImage($imageFile): string
    {
        if (!$imageFile->isValid()) {
            throw new \RuntimeException('Invalid image file');
        }
        return $imageFile->store('home_banners', 'public');
    }

    public static function deleteImage($imagePath): void
    {
        if ($imagePath && Storage::disk('public')->exists($imagePath)) {
            Storage::disk('public')->delete($imagePath);
        }
    }

    public static function rearrangeAfterDelete(int $deletedArrangement): void
    {
        self::where('arrangement', '>', $deletedArrangement)
            ->decrement('arrangement');
    }
}
