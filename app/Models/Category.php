<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Translatable\HasTranslations;

class Category extends Model
{
    use HasFactory, LogsActivity, HasTranslations;

    public $translatable = ['name'];

    protected $fillable = [
        'name',
        'image',
        'arrangement',
        'is_active',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'image', 'arrangement', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('category');
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return strtolower(class_basename($this)) . '.' . $eventName;
    }
    protected function image(): Attribute
    {
        return new Attribute(
            get: function () {
                return asset(Storage::url($this->attributes['image']));
            }
        );
    }

       public static function getNextArrangement()
    {
        $maxArrangement = self::max('arrangement') ?? 0;
        return $maxArrangement + 1;
    }

    public static function updateArrangement(Category $category, $newArrangement)
    {
        if ($category->arrangement != $newArrangement) {
            self::where('arrangement', $newArrangement)->update(['arrangement' => $category->arrangement]);
            return $newArrangement;
        }
        return $category->arrangement;
    }

    public static function rearrangeAfterDelete($deletedArrangement)
    {
        self::where('arrangement', '>', $deletedArrangement)
            ->decrement('arrangement');
    }

    public static function storeImage($imageFile)
    {
        return $imageFile->store('categories', 'public');
    }

    public static function deleteImage($imagePath)
    {
        if ($imagePath && Storage::disk('public')->exists($imagePath)) {
            Storage::disk('public')->delete($imagePath);
        }
    }
}
