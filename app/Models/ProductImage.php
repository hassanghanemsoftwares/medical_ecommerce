<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;
class ProductImage extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'product_id',
        'image',
        'is_active',
        'arrangement',
    ];
    protected $image_path = "products/";


 
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'product_id',
                'image',
                'is_active',
                'arrangement',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('product_image');
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return strtolower(class_basename($this)) . '.' . $eventName;
    }

    protected function image(): Attribute
    {
        return new Attribute(
            get: function () {
                return Storage::url($this->image_path . $this->attributes['image']);
            }
        );
    }
}
