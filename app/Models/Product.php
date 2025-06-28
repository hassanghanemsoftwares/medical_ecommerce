<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\DB;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\Translatable\HasTranslations;

class Product extends Model
{
    use HasFactory, LogsActivity, HasSlug, HasTranslations;

    protected $fillable = [
        'barcode',
        'slug',
        'availability_status',
        'category_id',
        'brand_id',
        'name',
        'short_description',
        'description',
        'price',
        'discount',
        'min_order_quantity',
        'max_order_quantity',
    ];
    public $translatable = [
        'name',
        'short_description',
        'description',
    ];
    protected $appends = ['final_price'];


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
    public function stockAdjustments()
    {
        return $this->hasMany(StockAdjustment::class);
    }
    public function specifications()
    {
        return $this->hasMany(ProductSpecification::class);
    }
    public function homeProductSectionItems()
    {
        return $this->hasMany(HomeProductSectionItem::class, 'product_id');
    }

    public function getFinalPriceAttribute()
    {
        return $this->price - ($this->price * $this->discount / 100);
    }
    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom(function ($model) {
                return $model->name . ' ' . $model->barcode;
            })
            ->saveSlugsTo('slug')
            ->slugsShouldBeNoLongerThan(80)
            ->doNotGenerateSlugsOnUpdate()
            ->usingSeparator('-')
            ->preventOverwrite();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'barcode',
                'slug',
                'availability_status',
                'category_id',
                'brand_id',
                'name',
                'short_description',
                'description',
                'price',
                'discount',
                'min_order_quantity',
                'max_order_quantity',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('Product');
    }


    public function getDescriptionForEvent(string $eventName): string
    {
        return strtolower(class_basename($this)) . '.' . $eventName;
    }
    public static function generateBarcode(): string
    {
        $prefix = '990';

        $lastBarcode = DB::table('products')
            ->where('barcode', 'like', $prefix . '%')
            ->orderBy('barcode', 'desc')
            ->value('barcode');

        if ($lastBarcode) {
            $nextNumber = intval(substr($lastBarcode, strlen($prefix))) + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }

    public function updateAvailabilityStatus(): void
    {
        $totalQuantity = $this->variants()
            ->with('stocks')
            ->get()
            ->flatMap->stocks
            ->sum('quantity');

        if ($totalQuantity <= 0) {
            $this->availability_status = 'out_of_stock';
            $this->saveQuietly();
        }
    }
}
