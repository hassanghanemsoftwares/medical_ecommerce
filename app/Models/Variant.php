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

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['product_id', 'size_id', 'color_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('variant');
    }

    public function getDescriptionForEvent(string $eventName): string
    {
        return strtolower(class_basename($this)) . '.' . $eventName;
    }

    public static function generateSku($product, $colorId, $sizeId): string
    {
        // Decode JSON if it's a string
        $name = $product->name;
        if (is_string($name)) {
            $name = json_decode($name, true);
        }

        $sku = strtoupper(Str::slug($name['en'] ?? $name['ar'] ?? $product->name));
        if ($colorId) {
            $sku .= '-C' . $colorId;
        }
        if ($sizeId) {
            $sku .= '-S' . $sizeId;
        }
        return $sku . '-' . uniqid();
    }
}
