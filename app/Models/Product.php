<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\DB;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
class Product extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'barcode',
        'slug',
        'is_active',
        'category_id',
        'brand_id',
        'name',
        'description',
        'price',
        'discount',
        'min_quantity',
        'max_quantity',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }
    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'product_tags');
    }
    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }
    public function clicks()
    {
        return $this->hasMany(ProductClick::class);
    }

    public function variants()
    {
        return $this->hasMany(Variant::class);
    }


    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'barcode',
                'slug',
                'is_active',
                'category_id',
                'brand_id',
                'name',
                'description',
                'price',
                'discount',
                'min_quantity',
                'max_quantity',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('product');
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return strtolower(class_basename($this)) . '.' . $eventName;
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom(function ($model) {
                return $model->barcode . ' ' . $model->name_en;
            })
            ->saveSlugsTo('slug');
    }

    public static function generateBarcode()
    {
        $prefix = '990'; // Starting prefix
        $lastBarcode = DB::table('products')
            ->where('barcode', 'like', $prefix . '%')
            ->orderBy('barcode', 'desc')
            ->value('barcode');

        if ($lastBarcode) {
            $nextNumber = intval(substr($lastBarcode, 3)) + 1;
        } else {
            $nextNumber = 0;
        }

        return $prefix . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }
}
