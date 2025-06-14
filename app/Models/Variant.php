<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Str;

class Variant extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'product_id',
        'size_id',
        'color_id',
        'sku',
        'is_active',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function size()
    {
        return $this->belongsTo(Size::class);
    }

    public function color()
    {
        return $this->belongsTo(Color::class);
    }

    public function stockAdjustments()
    {
        return $this->hasMany(StockAdjustment::class);
    }
    public function stocks()
    {
         return $this->hasMany(Stock::class, 'variant_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['product_id', 'size_id', 'color_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('Variant');
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return strtolower(class_basename($this)) . '.' . $eventName;
    }



    public static function generateSku($product, $colorId = null, $sizeId = null): string
    {
        $name = is_string($product->name) ? json_decode($product->name, true) : $product->name;
        $name = is_array($name) ? $name : ['en' => (string) $product->name];
        $base = strtoupper(Str::slug($name['en'] ?? $name['ar'] ?? 'product'));

        if ($colorId && ($color = Color::find($colorId))) {
            $colorName = is_string($color->name) ? json_decode($color->name, true) : $color->name;
            $colorName = is_array($colorName) ? $colorName : ['en' => (string) $color->name];
            $base .= '-' . strtoupper(Str::slug($colorName['en'] ?? $colorName['ar'] ?? ''));
        }

        if ($sizeId && ($size = Size::find($sizeId))) {
            $sizeName = is_string($size->name) ? json_decode($size->name, true) : $size->name;
            $sizeName = is_array($sizeName) ? $sizeName : ['en' => (string) $size->name];
            $base .= '-' . strtoupper(Str::slug($sizeName['en'] ?? $sizeName['ar'] ?? ''));
        }

        do {
            $sku = $base . '-' . strtoupper(Str::random(4));
        } while (self::where('sku', $sku)->exists());

        return $sku;
    }

    public function getDisplaySkuAttribute(): string
    {
        $parts = [];

        // Product Name
        $productName = $this->product?->name;
        $productName = is_string($productName) ? json_decode($productName, true) : $productName;
        $productName = is_array($productName) ? $productName : ['en' => (string) $this->product?->name];
        $parts[] = strtoupper($productName['en'] ?? $productName['ar'] ?? 'PRODUCT');

        // Color Name
        if ($this->color) {
            $colorName = is_string($this->color->name) ? json_decode($this->color->name, true) : $this->color->name;
            $colorName = is_array($colorName) ? $colorName : ['en' => (string) $this->color->name];
            $parts[] = strtoupper($colorName['en'] ?? $colorName['ar'] ?? '');
        }

        // Size Name
        if ($this->size) {
            $sizeName = is_string($this->size->name) ? json_decode($this->size->name, true) : $this->size->name;
            $sizeName = is_array($sizeName) ? $sizeName : ['en' => (string) $this->size->name];
            $parts[] = strtoupper($sizeName['en'] ?? $sizeName['ar'] ?? '');
        }

        return implode('-', array_filter($parts));
    }
}
